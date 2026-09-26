<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Domain\Enums;

enum WebinarStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::Published => 'منتشرشده',
            self::Cancelled => 'لغوشده',
        };
    }
}
