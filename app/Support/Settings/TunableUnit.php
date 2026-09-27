<?php

declare(strict_types=1);

namespace App\Support\Settings;

enum TunableUnit: string
{
    case Toman = 'toman';
    case Percent = 'percent';
    case Minutes = 'minutes';
    case Hours = 'hours';
    case Days = 'days';
    case Months = 'months';
    case Count = 'count';

    public function label(): string
    {
        return match ($this) {
            self::Toman => 'تومان',
            self::Percent => 'درصد',
            self::Minutes => 'دقیقه',
            self::Hours => 'ساعت',
            self::Days => 'روز',
            self::Months => 'ماه',
            self::Count => 'عدد',
        };
    }
}
