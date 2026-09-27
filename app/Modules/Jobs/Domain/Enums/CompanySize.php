<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain\Enums;

/** اندازه شرکت، به اظهار کارفرما. */
enum CompanySize: string
{
    case Micro = '1-10';
    case Small = '11-50';
    case Medium = '51-200';
    case Large = '201-1000';
    case Enterprise = '1000+';

    public function label(): string
    {
        return match ($this) {
            self::Micro => 'تا ۱۰ نفر',
            self::Small => '۱۱ تا ۵۰ نفر',
            self::Medium => '۵۱ تا ۲۰۰ نفر',
            self::Large => '۲۰۱ تا ۱۰۰۰ نفر',
            self::Enterprise => 'بیش از ۱۰۰۰ نفر',
        };
    }
}
