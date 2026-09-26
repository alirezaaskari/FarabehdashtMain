<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Modules\Webinars\Actions\CompleteRegistration;
use App\Modules\Webinars\Actions\PayRegistrationFromWallet;
use App\Modules\Webinars\Actions\Register;
use App\Modules\Webinars\Actions\StartRegistrationCheckout;
use App\Modules\Webinars\Domain\Enums\RegistrationStatus;
use App\Modules\Webinars\Domain\Webinar;
use App\Modules\Webinars\Domain\WebinarRegistration;
use App\Support\Payments\InsufficientWalletBalance;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/** ثبت‌نام رایگان، یا پولی با درگاه یا کیف پول؛ همه به صفحه رویداد برمی‌گردند. */
final readonly class RegistrationController
{
    private const string DONE = 'ثبت‌نام شما قطعی شد. پیوند ورود از یک ساعت پیش از شروع همین‌جا باز می‌شود.';

    public function __construct(
        private Register $register,
        private StartRegistrationCheckout $startCheckout,
        private PayRegistrationFromWallet $payFromWallet,
        private CompleteRegistration $complete,
        private PaymentGateway $gateway,
    ) {}

    public function store(Request $request, Webinar $webinar): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        try {
            $registration = $this->register->handle($webinar, (int) $user->getKey());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['register' => $exception->getMessage()]);
        }

        $back = redirect()->route('webinars.show', $webinar->slug);

        if ($registration->isConfirmed()) {
            return $back->with('status', self::DONE);
        }

        if (PaymentSource::requested($request) === PaymentSource::Wallet) {
            try {
                $this->payFromWallet->handle($registration);
            } catch (InsufficientWalletBalance $exception) {
                $registration->forceFill(['status' => RegistrationStatus::Failed])->save();

                return back()->withErrors(['payment' => $exception->getMessage()]);
            }

            return $back->with('status', self::DONE);
        }

        try {
            $result = $this->startCheckout->handle($registration, $user->mobile ?? null);
        } catch (PaymentGatewayUnavailable $exception) {
            report($exception);

            return back()->withErrors(['register' => PaymentGatewayUnavailable::USER_MESSAGE]);
        }

        return redirect()->away($result->redirectUrl);
    }

    /** زرین‌پال بدون نشست کاربر هم ممکن است برگردد؛ مسیر عمداً بیرون از auth است. */
    public function callback(Request $request): RedirectResponse
    {
        $registration = WebinarRegistration::query()
            ->where('gateway_authority', (string) $request->query('Authority'))
            ->with('webinar')
            ->firstOrFail();

        $back = redirect()->route('webinars.show', $registration->webinar->slug);

        if ($registration->isConfirmed()) {
            return $back->with('status', self::DONE);
        }

        if ((string) $request->query('Status') !== 'OK' || $registration->status !== RegistrationStatus::Pending) {
            $registration->forceFill(['status' => RegistrationStatus::Failed])->save();

            return $back->withErrors(['register' => 'پرداخت توسط شما لغو شد.']);
        }

        $verification = $this->gateway->verify((string) $registration->gateway_authority, $registration->price());

        if (! $verification->successful) {
            $registration->forceFill(['status' => RegistrationStatus::Failed])->save();

            return $back->withErrors(['register' => $verification->failureReason ?? 'پرداخت تأیید نشد.']);
        }

        $this->complete->handle($registration, $verification->referenceId ?? '');

        return $back->with('status', self::DONE);
    }
}
