<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain\Enums;

/**
 * صنعت پروژه — مبنای قالب پیشنهادی اندازه‌گیری‌ها.
 *
 * فهرست هر قالب **پیشنهادی و عمومی** است. تعیین دامنه واقعی پایش بر عهده
 * کارشناس و بر اساس شناسایی عوامل زیان‌آور همان واحد است؛ این‌جا فقط نقطه
 * شروع است تا کسی از صفر شروع نکند.
 */
enum Industry: string
{
    case Foundry = 'foundry';
    case Petrochemical = 'petrochemical';
    case Hospital = 'hospital';
    case Automotive = 'automotive';
    case Mining = 'mining';
    case Textile = 'textile';
    case Construction = 'construction';
    case Printing = 'printing';

    public function label(): string
    {
        return match ($this) {
            self::Foundry => 'ریخته‌گری',
            self::Petrochemical => 'پتروشیمی',
            self::Hospital => 'بیمارستان',
            self::Automotive => 'خودروسازی',
            self::Mining => 'معدن',
            self::Textile => 'نساجی',
            self::Construction => 'ساختمان',
            self::Printing => 'چاپ',
        };
    }
}
