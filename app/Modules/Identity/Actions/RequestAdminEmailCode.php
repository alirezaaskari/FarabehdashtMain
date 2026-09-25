<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use App\Modules\Identity\Mail\AdminLoginCode;
use App\Modules\Identity\Services\AdminEmailAccount;
use App\Modules\Identity\Services\OtpService;
use Illuminate\Contracts\Mail\Mailer;

/**
 * فرستادن کد ورود به ایمیل مدیر.
 *
 * پاسخ بیرونی همیشه یکی است، چه ایمیل به مدیری برسد چه نرسد، چه سقف
 * درخواست پر شده باشد: فرم ورود نباید بگوید کدام ایمیل مدیر است.
 */
final readonly class RequestAdminEmailCode
{
    public function __construct(
        private AdminEmailAccount $accounts,
        private OtpService $otp,
        private Mailer $mailer,
    ) {}

    public function handle(string $email, ?string $ip = null): void
    {
        $user = $this->accounts->find($email);

        if ($user === null) {
            return;
        }

        $ttl = $this->accounts->ttlSeconds();

        try {
            $code = $this->otp->issue($user->mobile, OtpPurpose::AdminEmailLogin, $ip, $ttl);
        } catch (OtpException) {
            return;
        }

        $this->mailer->to((string) $user->email)->send(new AdminLoginCode($code, intdiv($ttl, 60)));
    }
}
