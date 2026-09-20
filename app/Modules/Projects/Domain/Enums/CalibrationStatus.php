<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain\Enums;

/**
 * وضعیت کالیبراسیون یک تجهیز.
 *
 * «نزدیک انقضا» عمداً حالت جداگانه‌ای است و نه یک پرچم: کارشناسی که فردا
 * می‌رود میدان، باید امروز بداند اعتبار دستگاهش هفته دیگر تمام می‌شود، نه
 * وقتی گزارش را می‌خواهد صادر کند.
 *
 * «ثبت‌نشده» هم با «منقضی» یکی نیست. اولی یعنی کاربر تاریخی وارد نکرده؛
 * دومی یعنی وارد کرده و گذشته است. قاطی‌کردنشان یعنی هشدار دروغ.
 */
enum CalibrationStatus: string
{
    case Valid = 'valid';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
    case NotRecorded = 'not_recorded';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'معتبر',
            self::ExpiringSoon => 'نزدیک انقضا',
            self::Expired => 'منقضی',
            self::NotRecorded => 'ثبت‌نشده',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Valid => 'primary',
            self::ExpiringSoon => 'caution',
            self::Expired => 'danger',
            self::NotRecorded => 'neutral',
        };
    }

    /**
     * آیا پیش از صدور گزارش باید هشدار داده شود و تأیید کاربر گرفته شود؟
     *
     * معیار پذیرش بخش ۸ روی همین متد می‌نشیند.
     */
    public function blocksReport(): bool
    {
        return $this === self::Expired || $this === self::NotRecorded;
    }
}
