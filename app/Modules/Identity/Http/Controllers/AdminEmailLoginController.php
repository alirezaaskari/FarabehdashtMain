<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\RequestAdminEmailCode;
use App\Modules\Identity\Actions\SignInAdminWithEmailCode;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use App\Modules\Identity\Http\Requests\VerifyCodeRequest;
use App\Modules\Identity\Services\AdminEmailAccount;
use App\Modules\Identity\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** ورود مدیر با کد ایمیلی؛ کنار ورود پیامکی، نه به‌جایش. */
final readonly class AdminEmailLoginController
{
    public const PENDING_EMAIL = 'identity.pending_admin_email';

    public function __construct(
        private RequestAdminEmailCode $request,
        private SignInAdminWithEmailCode $signIn,
        private AdminEmailAccount $accounts,
        private OtpService $otp,
    ) {}

    public function show(): View
    {
        return view('identity::admin-email');
    }

    public function store(Request $request): RedirectResponse
    {
        $email = (string) $request->validate(
            ['email' => ['required', 'email:rfc', 'max:190']],
            attributes: ['email' => 'ایمیل'],
        )['email'];

        $this->request->handle($email, $request->ip());
        $request->session()->put(self::PENDING_EMAIL, mb_strtolower(trim($email)));

        return to_route('identity.email.verify.show');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $email = $this->pendingEmail($request);

        if ($email === null) {
            return to_route('identity.email.show');
        }

        return view('identity::verify', [
            'destination' => $email,
            'codeLength' => $this->otp->codeLength(),
            // شمارش معکوس از حساب واقعی نمی‌آید تا ایمیل غیرمدیر لو نرود.
            'resendAfter' => $this->otp->resendAfterSeconds(),
            'verifyRoute' => 'identity.email.verify.store',
            'resendRoute' => 'identity.email.resend',
            'changeRoute' => 'identity.email.show',
            'changeLabel' => 'تغییر ایمیل',
            'hint' => 'اگر ایمیل را نمی‌بینید، پوشه هرزنامه را هم نگاه کنید. کد فقط برای ایمیل مدیر فرستاده می‌شود.',
        ]);
    }

    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        $email = $this->pendingEmail($request);

        if ($email === null) {
            return to_route('identity.email.show');
        }

        try {
            $this->signIn->handle($email, $request->code());
        } catch (OtpException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->forget(self::PENDING_EMAIL);
        $request->session()->regenerate();

        return redirect()->intended(route('identity.profiles'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = $this->pendingEmail($request);

        if ($email === null) {
            return to_route('identity.email.show');
        }

        $this->request->handle($email, $request->ip());

        return back()->with('status', 'اگر این ایمیل مدیر باشد، کد تازه فرستاده شد.');
    }

    private function pendingEmail(Request $request): ?string
    {
        $email = $request->session()->get(self::PENDING_EMAIL);

        return is_string($email) && $email !== '' ? $email : null;
    }
}
