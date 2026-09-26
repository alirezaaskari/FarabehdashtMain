<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Modules\ExamPrep\Actions\CompletePackPurchase;
use App\Modules\ExamPrep\Actions\OpenPackPurchase;
use App\Modules\ExamPrep\Actions\PayPackFromWallet;
use App\Modules\ExamPrep\Actions\StartPackCheckout;
use App\Modules\ExamPrep\Domain\Enums\PurchaseStatus;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Domain\PackPurchase;
use App\Support\Payments\InsufficientWalletBalance;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/** خرید بسته آزمون با درگاه یا کیف پول؛ هر دو به صفحه بسته برمی‌گردند. */
final readonly class PackPurchaseController
{
    private const string PAID = 'خرید انجام شد. تمرین و آزمون این بسته باز است.';

    public function __construct(
        private OpenPackPurchase $open,
        private StartPackCheckout $startCheckout,
        private PayPackFromWallet $payFromWallet,
        private CompletePackPurchase $complete,
        private PaymentGateway $gateway,
    ) {}

    public function store(Request $request, ExamPack $pack): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        try {
            $purchase = $this->open->handle($pack, (int) $user->getKey());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['purchase' => $exception->getMessage()]);
        }

        if (PaymentSource::requested($request) === PaymentSource::Wallet) {
            try {
                $this->payFromWallet->handle($purchase);
            } catch (InsufficientWalletBalance $exception) {
                $purchase->forceFill(['status' => PurchaseStatus::Failed])->save();

                return back()->withErrors(['payment' => $exception->getMessage()]);
            }

            return redirect()->route('exam_prep.show', $pack->slug)->with('status', self::PAID);
        }

        try {
            $result = $this->startCheckout->handle($purchase, $user->mobile ?? null);
        } catch (PaymentGatewayUnavailable $exception) {
            report($exception);

            return back()->withErrors(['purchase' => PaymentGatewayUnavailable::USER_MESSAGE]);
        }

        return redirect()->away($result->redirectUrl);
    }

    /** زرین‌پال بدون نشست کاربر هم ممکن است برگردد؛ مسیر عمداً بیرون از auth است. */
    public function callback(Request $request): RedirectResponse
    {
        $purchase = PackPurchase::query()
            ->where('gateway_authority', (string) $request->query('Authority'))
            ->with('pack')
            ->firstOrFail();

        $back = redirect()->route('exam_prep.show', $purchase->pack->slug);

        if ($purchase->isPaid()) {
            return $back->with('status', self::PAID);
        }

        if ((string) $request->query('Status') !== 'OK') {
            $purchase->forceFill(['status' => PurchaseStatus::Failed])->save();

            return $back->withErrors(['purchase' => 'پرداخت توسط شما لغو شد.']);
        }

        $verification = $this->gateway->verify((string) $purchase->gateway_authority, $purchase->price());

        if (! $verification->successful) {
            $purchase->forceFill(['status' => PurchaseStatus::Failed])->save();

            return $back->withErrors(['purchase' => $verification->failureReason ?? 'پرداخت تأیید نشد.']);
        }

        $this->complete->handle($purchase, $verification->referenceId ?? '');

        return $back->with('status', self::PAID);
    }
}
