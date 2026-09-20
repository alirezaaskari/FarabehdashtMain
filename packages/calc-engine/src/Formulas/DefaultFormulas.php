<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas;

use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\FormulaRegistry;
use Farabehdasht\CalcEngine\Formulas\Noise\SoundPressureSumV1;
use Farabehdasht\CalcEngine\Formulas\Wbgt\WbgtIndoorV1;
use Farabehdasht\CalcEngine\Formulas\Wbgt\WbgtOutdoorV1;

/**
 * فهرست فرمول‌هایی که همراه موتور می‌آیند.
 *
 * نسخه تازه یک فرمول یعنی یک کلاس تازه در این فهرست، کنار نسخه قدیم — نه
 * جایگزینش. نسخه قدیم می‌ماند تا محاسبه‌های ذخیره‌شده بازتولید شوند.
 */
final class DefaultFormulas
{
    /**
     * @return list<Formula>
     */
    public static function all(): array
    {
        return [
            new WbgtIndoorV1,
            new WbgtOutdoorV1,
            new SoundPressureSumV1,
        ];
    }

    public static function registry(): FormulaRegistry
    {
        return new FormulaRegistry(self::all());
    }
}
