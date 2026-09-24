<?php

declare(strict_types=1);

namespace App\Support\Entitlement;

/**
 * چرا لایه دسترسی اجازه داد یا نداد.
 *
 * دلیل، هم پاسخ آری/نه را در خود دارد (`allows()`) و هم شکل صفحه را تعیین
 * می‌کند: «۵ از ۵ محاسبه پر شده» با «این امکان فقط برای مشترک است» دو پیام
 * و دو دکمه متفاوت‌اند. بولین ساده هر دو را یکی می‌کرد و منطق را به قالب
 * برمی‌گرداند — نقض قاعده ۴.
 *
 * `StreamDisabled` اجازه می‌دهد، نه رد: وقتی مدیر کلید اشتراک را خاموش کند،
 * امکانی که پشت اشتراک بود برای همه باز می‌شود. همین یک case معیار پذیرش
 * بخش ۱۴ را ممکن می‌کند بدون تغییر کد هیچ صفحه‌ای.
 */
enum EntitlementReason: string
{
    case Subscribed = 'subscribed';
    case WithinFreeQuota = 'within_free_quota';
    case StreamDisabled = 'stream_disabled';
    case SignInRequired = 'sign_in_required';
    case QuotaExhausted = 'quota_exhausted';
    case RequiresPro = 'requires_pro';

    public function allows(): bool
    {
        return match ($this) {
            self::Subscribed, self::WithinFreeQuota, self::StreamDisabled => true,
            self::SignInRequired, self::QuotaExhausted, self::RequiresPro => false,
        };
    }
}
