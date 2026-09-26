<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain\Enums;

enum PackStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::Published => 'منتشرشده',
            self::Retired => 'بایگانی',
        };
    }
}
