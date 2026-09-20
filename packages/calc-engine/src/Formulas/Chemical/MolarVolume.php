<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Chemical;

/**
 * حجم مولی گاز کامل، بر حسب لیتر بر مول.
 *
 *     Vm = R × T / P
 *
 * چرا از قانون گاز کامل و نه عدد ثابت ۲۴٫۴۵: تبدیل ppm و mg/m³ به دما و فشار
 * وابسته است و ثابت‌گرفتنش یعنی هر اندازه‌گیری بیرون از شرایط مرجع بی‌صدا
 * اشتباه شود. اختلافش با جدول‌های قدیمی در محدودیت‌های هر دو فرمول تبدیل
 * نوشته شده است.
 */
final class MolarVolume
{
    /**
     * ثابت جهانی گازها — CODATA 2018.
     *
     * واحدش این‌جا L·kPa/(mol·K) است و با J/(mol·K) عددش یکی است،
     * چون ۱ کیلوپاسکال در لیتر برابر ۱ ژول است.
     */
    public const float GAS_CONSTANT = 8.314462618;

    public const float ABSOLUTE_ZERO_CELSIUS = -273.15;

    public static function litresPerMole(float $celsius, float $kilopascal): float
    {
        return self::GAS_CONSTANT * ($celsius - self::ABSOLUTE_ZERO_CELSIUS) / $kilopascal;
    }
}
