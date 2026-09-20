<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Enums;

/**
 * وضعیت یک ماده در بانک.
 *
 * ماده بازنشسته نشانی‌اش را نگه می‌دارد: صفحه ماده «پایدار و قابل استناد»
 * است و گزارش‌های کارشناسی به آن پیوند داده‌اند.
 */
enum SubstanceStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::Published => 'منتشرشده',
            self::Retired => 'بازنشسته',
        };
    }

    public function publiclyVisible(): bool
    {
        return $this === self::Published;
    }
}
