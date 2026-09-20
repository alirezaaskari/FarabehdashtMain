<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Domain\Enums;

/**
 * وضعیت یک محتوا در چرخه سرمقاله‌ای.
 *
 * «بازنشسته» با «پیش‌نویس» یکی نیست: محتوای بازنشسته روزی منتشر بوده، نشانی
 * دارد و ممکن است هنوز از بیرون به آن پیوند داده شده باشد.
 */
enum ArticleStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::InReview => 'در انتظار بازبینی',
            self::Published => 'منتشرشده',
            self::Retired => 'بازنشسته',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::InReview => 'caution',
            self::Published => 'primary',
            self::Retired => 'neutral',
        };
    }

    /** فقط محتوای منتشرشده نشانی عمومی دارد و در نقشه سایت می‌آید. */
    public function publiclyVisible(): bool
    {
        return $this === self::Published;
    }
}
