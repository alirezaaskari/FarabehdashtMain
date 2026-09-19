<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Contracts\PanelAccess;
use App\Models\User;

/**
 * پاسخ به پرسش Filament درباره ورود به پنل.
 *
 * شناسه پنل بررسی می‌شود تا اگر روزی پنل دومی اضافه شد (مثلاً پنل فروشنده)،
 * این کلاس به‌اشتباه به آن هم اجازه ندهد.
 */
final readonly class PanelGatekeeper implements PanelAccess
{
    public function __construct(private AdminAccess $access) {}

    public function canAccessPanel(User $user, string $panelId): bool
    {
        return $panelId === 'fbh' && $this->access->isAdmin($user);
    }
}
