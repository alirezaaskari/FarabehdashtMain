<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\SignInWithOtp;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use App\Modules\Identity\Http\Requests\VerifyCodeRequest;
use App\Modules\Identity\Services\OtpService;
use App\Support\Mobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final readonly class VerifyCodeController
{
    public function __construct(
        private OtpService $otp,
        private SignInWithOtp $signIn,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $mobile = $this->pendingMobile($request);

        if (! $mobile instanceof Mobile) {
            return to_route('login');
        }

        return view('identity::verify', [
            'mobile' => $mobile,
            'codeLength' => $this->otp->codeLength(),
            'resendAfter' => $this->otp->secondsUntilResend($mobile->value, OtpPurpose::Login),
        ]);
    }

    public function store(VerifyCodeRequest $request): RedirectResponse
    {
        $mobile = $this->pendingMobile($request);

        if (! $mobile instanceof Mobile) {
            return to_route('login');
        }

        try {
            $this->signIn->handle($mobile, $request->code());
        } catch (OtpException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->forget(LoginController::PENDING_MOBILE);
        $request->session()->regenerate();

        // صفحه‌ای که مهمان را به ورود فرستاد (مثلاً پنل مدیریت) مقدم است.
        return redirect()->intended(route('identity.profiles'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $mobile = $this->pendingMobile($request);

        if (! $mobile instanceof Mobile) {
            return to_route('login');
        }

        try {
            $this->otp->request($mobile->value, OtpPurpose::Login, $request->ip());
        } catch (OtpException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with('status', 'کد تازه برای شما ارسال شد.');
    }

    private function pendingMobile(Request $request): ?Mobile
    {
        $mobile = $request->session()->get(LoginController::PENDING_MOBILE);

        return is_string($mobile) ? Mobile::tryFromInput($mobile) : null;
    }
}
