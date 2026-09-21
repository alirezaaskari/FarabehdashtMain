<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain\Enums;

/**
 * وضعیت یک محصول دیجیتال در چرخه بررسی.
 *
 * قاعده محصول: محتوای فروشنده پیش از انتشار نیازمند تأیید مدیر است. «رد شده»
 * با «پیش‌نویس» یکی نیست: فروشنده باید بداند چرا رد شده (`review_note`) و
 * می‌تواند دوباره برای بازبینی بفرستد. «بازنشسته» با هیچ‌کدام یکی نیست:
 * محصولی که روزی منتشر بوده و خریدار دارد، هرگز به پیش‌نویس برنمی‌گردد —
 * فقط از فروش خارج می‌شود؛ خریداران قبلی دسترسی دانلودشان را نگه می‌دارند.
 */
enum ProductStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::InReview => 'در انتظار بازبینی',
            self::Published => 'منتشرشده',
            self::Rejected => 'رد شده',
            self::Retired => 'بازنشسته',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::InReview => 'caution',
            self::Published => 'primary',
            self::Rejected => 'danger',
            self::Retired => 'neutral',
        };
    }

    /** فقط محصول منتشرشده در فروشگاه دیده و قابل خرید است. */
    public function purchasable(): bool
    {
        return $this === self::Published;
    }
}
