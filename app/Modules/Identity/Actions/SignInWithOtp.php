<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use App\Modules\Identity\Services\OtpService;
use App\Support\Mobile;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\DatabaseManager;

/**
 * ورود با کد تأیید.
 *
 * اگر حسابی با این شماره نباشد، همین‌جا ساخته می‌شود؛ ثبت‌نام جداگانه وجود ندارد.
 * حساب تازه هیچ پروفایل تجاری ندارد — فقط نقش پایه.
 */
final readonly class SignInWithOtp
{
    public function __construct(
        private OtpService $otp,
        private StatefulGuard $guard,
        private DatabaseManager $db,
    ) {}

    /**
     * @throws OtpException
     */
    public function handle(Mobile $mobile, string $code): User
    {
        $this->otp->verify($mobile->value, OtpPurpose::Login, $code);

        $user = $this->db->transaction(function () use ($mobile): User {
            $user = User::query()->firstOrNew(['mobile' => $mobile->value]);

            if (! $user->exists) {
                $user->status = UserStatus::Active;
            }

            // حساب معلق نباید با تأیید تازه شماره، دوباره فعال به نظر برسد.
            if (! $user->canSignIn()) {
                throw OtpException::accountSuspended();
            }

            $user->mobile_verified_at ??= now();
            $user->save();

            return $user;
        });

        $this->guard->login($user, remember: true);

        return $user;
    }
}
