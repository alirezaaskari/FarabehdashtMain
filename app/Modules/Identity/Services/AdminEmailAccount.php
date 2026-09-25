<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Contracts\PanelAccess;
use App\Models\User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;

/**
 * حسابی که با این ایمیل اجازه ورود با رمز عبور دارد، یا هیچ.
 *
 * چهار شرط، همه لازم: ایمیل تأییدشده (فقط `fbh:make-admin --email` تأییدش
 * می‌کند و تغییرش در «حساب من» تأیید را پاک می‌کند)، رمز عبور ثبت‌شده (فقط
 * از همان دستور)، حساب فعال، و راه به پنل مدیریت. اگر ماژول Admin نباشد،
 * هیچ‌کس با ایمیل وارد نمی‌شود.
 */
final readonly class AdminEmailAccount
{
    public function __construct(
        private Container $container,
        private Config $config,
    ) {}

    public function find(string $email): ?User
    {
        if (! $this->container->bound(PanelAccess::class)) {
            return null;
        }

        $user = User::query()
            ->where('email', self::normalize($email))
            ->whereNotNull('email_verified_at')
            ->whereNotNull('password')
            ->first();

        if (! $user instanceof User || ! $user->canSignIn()) {
            return null;
        }

        $panel = (string) $this->config->get('identity.admin_email_login.panel', 'fbh');

        return $this->container->make(PanelAccess::class)->canAccessPanel($user, $panel) ? $user : null;
    }

    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
