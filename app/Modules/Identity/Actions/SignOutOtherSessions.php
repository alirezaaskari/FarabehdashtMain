<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Events\OtherSessionsSignedOut;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

/**
 * خروج از همه دستگاه‌های دیگر.
 *
 * `logoutOtherDevices` خود لاراول رمز عبور می‌خواهد و این‌جا ورود فقط با کد
 * پیامکی است. پس دو کار دستی: نشست‌های دیگر همین کاربر از جدول نشست پاک
 * می‌شوند، و توکن «مرا به خاطر بسپار» عوض می‌شود تا کوکی قدیمی دستگاه دیگر
 * نشست تازه نسازد.
 */
final readonly class SignOutOtherSessions
{
    public function __construct(
        private DatabaseManager $db,
        private Config $config,
        private Dispatcher $events,
    ) {}

    /** آیا نشست‌ها در پایگاه داده‌اند و بستن‌شان از این‌جا ممکن است. */
    public function supported(): bool
    {
        return $this->config->get('session.driver') === 'database';
    }

    /** @return int شمار نشست‌های بسته‌شده */
    public function handle(User $user, string $currentSessionId): int
    {
        $closed = 0;

        if ($this->supported()) {
            $closed = $this->db->connection($this->config->get('session.connection'))
                ->table((string) $this->config->get('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        $user->forceFill(['remember_token' => Str::random(60)])->save();

        $this->events->dispatch(new OtherSessionsSignedOut($user, $closed));

        return $closed;
    }

    /** @return int شمار نشست‌های فعال دیگر، یا null اگر معلوم نیست */
    public function otherSessionCount(User $user, string $currentSessionId): ?int
    {
        if (! $this->supported()) {
            return null;
        }

        return $this->db->connection($this->config->get('session.connection'))
            ->table((string) $this->config->get('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->where('id', '!=', $currentSessionId)
            ->count();
    }
}
