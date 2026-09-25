<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Domain\Exceptions\AdminSignInFailed;
use App\Modules\Identity\Events\UserSignedIn;
use App\Modules\Identity\Services\AdminEmailAccount;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * ورود مدیر با ایمیل و رمز عبور.
 *
 * پاسخ نادرست همیشه یکی است، چه ایمیل مال مدیر نباشد چه رمز غلط باشد، و در
 * هر دو حالت یک هش بررسی می‌شود تا زمان پاسخ هم لو ندهد کدام ایمیل مدیر است.
 * هر ایمیل از هر IP چند تلاش دارد و بعد قفل می‌شود.
 *
 * حساب نمی‌سازد و «مرا به خاطر بسپار» نمی‌گذارد: نشست مدیر با بستن مرورگر
 * تمام می‌شود.
 */
final readonly class SignInAdminWithPassword
{
    /** هش ثابت برای بررسی ساختگی وقتی حسابی نیست. */
    private const DUMMY_HASH = '$2y$12$Q.eVM8EcOzNV9pHJcdD2a.AX.kMLGczV4J33glvoIbLb4B0oAaylq';

    public function __construct(
        private AdminEmailAccount $accounts,
        private StatefulGuard $guard,
        private Hasher $hasher,
        private RateLimiter $limiter,
        private Config $config,
        private Dispatcher $events,
    ) {}

    /**
     * @throws AdminSignInFailed
     */
    public function handle(string $email, string $password, ?string $ip = null): User
    {
        $key = 'admin-password:'.sha1(AdminEmailAccount::normalize($email).'|'.$ip);
        $maxAttempts = (int) $this->config->get('identity.admin_email_login.max_attempts', 5);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            throw AdminSignInFailed::lockedOut($this->limiter->availableIn($key));
        }

        $user = $this->accounts->find($email);
        $valid = $this->hasher->check($password, $user?->getAuthPassword() ?? self::DUMMY_HASH);

        if (! $user instanceof User || ! $valid) {
            $this->limiter->hit($key, (int) $this->config->get('identity.admin_email_login.lockout_seconds', 900));

            throw AdminSignInFailed::rejected();
        }

        $this->limiter->clear($key);
        $this->guard->login($user);
        $this->events->dispatch(new UserSignedIn($user, false));

        return $user;
    }
}
