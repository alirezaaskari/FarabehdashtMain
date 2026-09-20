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
    case Hour = 'hour';
    case PartsPerMillion = 'parts_per_million';
    case MilligramPerCubicMetre = 'milligram_per_cubic_metre';
    case GramPerMole = 'gram_per_mole';
    case LitrePerMole = 'litre_per_mole';
    case Kilopascal = 'kilopascal';
    case Lux = 'lux';
    case CubicMetre = 'cubic_metre';
    case CubicMetrePerHour = 'cubic_metre_per_hour';
    case PerHour = 'per_hour';
    case Ratio = 'ratio';

    /**
     * کمیت بی‌بعد نماد ندارد و لایه نمایش نباید چیزی کنار عددش بگذارد.
     */
    public function dimensionless(): bool
    {
        return $this === self::Ratio;
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Celsius => '°C',
            self::Decibel => 'dB',
            self::Percent => '%',
            self::MetrePerSecond => 'm/s',
            self::Minute => 'min',
            self::Hour => 'h',
            self::PartsPerMillion => 'ppm',
            self::MilligramPerCubicMetre => 'mg/m³',
            self::GramPerMole => 'g/mol',
            self::LitrePerMole => 'L/mol',
            self::Kilopascal => 'kPa',
            self::Lux => 'lx',
            self::CubicMetre => 'm³',
            self::CubicMetrePerHour => 'm³/h',
            self::PerHour => '1/h',
            self::Ratio => '',
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
            self::Hour => 'ساعت',
            self::PartsPerMillion => 'قسمت در میلیون',
            self::MilligramPerCubicMetre => 'میلی‌گرم بر مترمکعب',
            self::GramPerMole => 'گرم بر مول',
            self::LitrePerMole => 'لیتر بر مول',
            self::Kilopascal => 'کیلوپاسکال',
            self::Lux => 'لوکس',
            self::CubicMetre => 'مترمکعب',
            self::CubicMetrePerHour => 'مترمکعب بر ساعت',
            self::PerHour => 'بار در ساعت',
            self::Ratio => 'نسبت بی‌بعد',
        };
    }
}
