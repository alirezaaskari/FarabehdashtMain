<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Domain\Enums;

/**
 * نشانگر تازگی داده.
 *
 * خواننده باید بدون خواندن تاریخ بفهمد این متن چقدر تازه است. سه حالت و نه
 * بیشتر؛ طیف پیوسته به خواننده کمکی نمی‌کند.
 *
 * «ثبت‌نشده» عمداً حالت چهارم نیست: محتوای بدون بازبینی اصلاً منتشر نمی‌شود،
 * پس در صفحه عمومی هرگز دیده نمی‌شود.
 */
enum Freshness: string
{
    case Fresh = 'fresh';
    case Aging = 'aging';
    case Stale = 'stale';

    public function label(): string
    {
        return match ($this) {
            self::Fresh => 'بازبینی‌شده و تازه',
            self::Aging => 'نزدیک به موعد بازبینی',
            self::Stale => 'نیازمند بازبینی',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Fresh => 'primary',
            self::Aging => 'caution',
            self::Stale => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Fresh => 'check',
            self::Aging => 'clock',
            self::Stale => 'alert',
        };
    }

    /** آیا خواننده باید هشدار ببیند که متن شاید عقب باشد. */
    public function warnsReader(): bool
    {
        return $this === self::Stale;
    }
}
