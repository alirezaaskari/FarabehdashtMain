<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine;

/**
 * واحدهایی که موتور می‌شناسد.
 *
 * هر کمیت در موتور واحد دارد و واحد تبدیل نمی‌شود: اگر فرمولی سلسیوس می‌خواهد،
 * فقط سلسیوس می‌پذیرد. تبدیل واحد جای دیگری است، نه این‌جا — چون تبدیل ضمنی
 * دقیقاً همان‌جایی است که خطای عددی خاموش می‌ماند.
 *
 * نماد واحد لاتین و چپ‌به‌راست است و لایه نمایش باید آن را data-numeric کند.
 */
enum Unit: string
{
    case Celsius = 'celsius';
    case Decibel = 'decibel';
    case Percent = 'percent';
    case MetrePerSecond = 'metre_per_second';
    case Minute = 'minute';

    public function symbol(): string
    {
        return match ($this) {
            self::Celsius => '°C',
            self::Decibel => 'dB',
            self::Percent => '%',
            self::MetrePerSecond => 'm/s',
            self::Minute => 'min',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Celsius => 'درجه سلسیوس',
            self::Decibel => 'دسی‌بل',
            self::Percent => 'درصد',
            self::MetrePerSecond => 'متر بر ثانیه',
            self::Minute => 'دقیقه',
        };
    }
}
