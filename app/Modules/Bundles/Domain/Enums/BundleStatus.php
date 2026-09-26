<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Domain\Enums;

enum BundleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::Published => 'منتشرشده',
            self::Retired => 'برداشته از فروش',
        };
    }
}
