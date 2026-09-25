<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Chemical;

/**
 * حجم مولی گاز کامل، بر حسب لیتر بر مول.
 *
 *     Vm = R × T / P
 *
 * نسخه ۱ فرمول‌های تبدیل از قانون گاز کامل استفاده می‌کند (۲۴٫۴۶۵ در شرایط
 * مرجع). نسخه ۲، طبق DEC-19، از عدد مرسوم ۲۴٫۴۵ شروع می‌کند و آن را به دما و
 * فشار اندازه‌گیری می‌برد؛ پس هیچ‌کدام اثر شرایط را نادیده نمی‌گیرد.
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

    /** حجم مولی مرسوم جدول‌ها در ۲۵ درجه سلسیوس و ۱۰۱٫۳۲۵ کیلوپاسکال (DEC-19). */
    public const float CONVENTIONAL_LITRES_PER_MOLE = 24.45;

    public const float REFERENCE_KELVIN = 298.15;

    public const float REFERENCE_KILOPASCAL = 101.325;

    public static function litresPerMole(float $celsius, float $kilopascal): float
    {
        return self::GAS_CONSTANT * ($celsius - self::ABSOLUTE_ZERO_CELSIUS) / $kilopascal;
    }

    /**
     * عدد مرسوم ۲۴٫۴۵ که با قانون گازها به دما و فشار اندازه‌گیری برده می‌شود:
     *
     *     Vm = 24.45 × (T / 298.15) × (101.325 / P)
     *
     * در شرایط مرجع دقیقاً ۲۴٫۴۵ است، پس نتیجه با جدول‌های مرسوم یکی می‌شود؛
     * بیرون از آن هنوز اثر دما و فشار را می‌بیند.
     */
    public static function conventionalLitresPerMole(float $celsius, float $kilopascal): float
    {
        $kelvin = $celsius - self::ABSOLUTE_ZERO_CELSIUS;

        return self::CONVENTIONAL_LITRES_PER_MOLE
            * ($kelvin / self::REFERENCE_KELVIN)
            * (self::REFERENCE_KILOPASCAL / $kilopascal);
    }
}
