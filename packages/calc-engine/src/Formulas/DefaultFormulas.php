<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas;

use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\FormulaRegistry;
use Farabehdasht\CalcEngine\Formulas\Chemical\MassConcentrationToPpmV1;
use Farabehdasht\CalcEngine\Formulas\Chemical\PpmToMassConcentrationV1;
use Farabehdasht\CalcEngine\Formulas\Chemical\TwaMassConcentrationV1;
use Farabehdasht\CalcEngine\Formulas\Chemical\TwaPpmV1;
use Farabehdasht\CalcEngine\Formulas\Lighting\IlluminanceUniformityV1;
use Farabehdasht\CalcEngine\Formulas\Noise\BackgroundNoiseCorrectionV1;
use Farabehdasht\CalcEngine\Formulas\Noise\EquivalentContinuousLevelV1;
use Farabehdasht\CalcEngine\Formulas\Noise\NoiseDoseV1;
use Farabehdasht\CalcEngine\Formulas\Noise\SoundPressureSumV1;
use Farabehdasht\CalcEngine\Formulas\Ventilation\AirChangesPerHourV1;
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
            // گرمایی
            new WbgtIndoorV1,
            new WbgtOutdoorV1,

            // صدا
            new SoundPressureSumV1,
            new BackgroundNoiseCorrectionV1,
            new EquivalentContinuousLevelV1,
            new NoiseDoseV1,

            // شیمیایی
            new PpmToMassConcentrationV1,
            new MassConcentrationToPpmV1,
            new TwaPpmV1,
            new TwaMassConcentrationV1,

            // روشنایی
            new IlluminanceUniformityV1,

            // تهویه
            new AirChangesPerHourV1,
        ];
    }

    public static function registry(): FormulaRegistry
    {
        return new FormulaRegistry(self::all());
    }
}
