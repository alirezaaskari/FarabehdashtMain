<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain\Enums;

/**
 * وضعیت یک دوره در چرخه بررسی — همان الگوی `ProductStatus` ماژول تجارت.
 *
 * محتوای مدرس هم مثل فروشنده پیش از انتشار نیازمند تأیید مدیر است (قاعده
 * محصول). «رد شده» با «پیش‌نویس» یکی نیست: مدرس باید بداند چرا رد شده.
 * «بازنشسته» با هیچ‌کدام یکی نیست: دانشجوهای ثبت‌نام‌شده دسترسی‌شان را
 * نگه می‌دارند؛ فقط ثبت‌نام تازه بسته می‌شود.
 */
enum CourseStatus: string
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

    /** فقط دوره منتشرشده در فهرست عمومی دیده و قابل ثبت‌نام است. */
    public function enrollable(): bool
    {
        return $this === self::Published;
    }
}
