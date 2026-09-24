<?php

declare(strict_types=1);

namespace App\Support\Entitlement;

use App\Contracts\EntitlementGate;
use App\Models\User;

/**
 * لایه دسترسی وقتی هیچ ماژول درآمدزایی‌ای وجود ندارد: همه‌چیز برای همه باز.
 *
 * این پیش‌فرض در `AppServiceProvider` بسته می‌شود و ماژول Monetization آن را
 * بازنویسی می‌کند. به همین دلیل هیچ مصرف‌کننده‌ای لازم نیست `app()->bound()`
 * را بررسی کند یا شاخه «اگر ماژول نبود» بنویسد — برداشتن یک خط از
 * `config/modules.php` کافی است تا کل سایت بی‌محدودیت شود.
 *
 * دلیلش `StreamDisabled` است، نه یک دلیل جدا: از دید صفحه، «ماژول نیست» و
 * «مدیر کلید را خاموش کرده» یک چیزند.
 */
final readonly class OpenGate implements EntitlementGate
{
    public function decide(?User $user, Feature $feature): Decision
    {
        return Decision::allow(EntitlementReason::StreamDisabled);
    }
}
