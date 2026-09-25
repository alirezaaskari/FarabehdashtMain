<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use App\Modules\Identity\Events\UserSignedIn;
use App\Modules\Identity\Services\AdminEmailAccount;
use App\Modules\Identity\Services\OtpService;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * ورود مدیر با کدی که به ایمیلش رفته.
 *
 * برخلاف ورود پیامکی حساب نمی‌سازد و «مرا به خاطر بسپار» نمی‌گذارد: نشست
 * مدیر با بستن مرورگر تمام می‌شود.
 */
final readonly class SignInAdminWithEmailCode
{
    public function __construct(
        private AdminEmailAccount $accounts,
        private OtpService $otp,
        private StatefulGuard $guard,
        private Dispatcher $events,
    ) {}

    /**
     * @throws OtpException
     */
    public function handle(string $email, string $code): User
    {
        $user = $this->accounts->find($email) ?? throw OtpException::emailCodeRejected();

        $this->otp->verify($user->mobile, OtpPurpose::AdminEmailLogin, $code);

        $this->guard->login($user);

        $this->events->dispatch(new UserSignedIn($user, false));

        return $user;
    }
}
