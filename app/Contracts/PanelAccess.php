<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * چه کسی اجازه ورود به یک پنل مدیریتی را دارد.
 *
 * Filament این را از خود مدل `User` می‌پرسد، ولی پاسخ به ماژول مدیریت مربوط
 * است نه به مدل کاربر. این قرارداد فاصله را نگه می‌دارد: `User` فقط می‌پرسد،
 * ماژول Admin جواب می‌دهد، و اگر آن ماژول نباشد پاسخ «نه» است.
 */
interface PanelAccess
{
    public function canAccessPanel(User $user, string $panelId): bool;
}
