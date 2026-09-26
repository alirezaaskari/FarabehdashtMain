<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Chemical;

use Farabehdasht\CalcEngine\Unit;

/**
 * شاخص مواجهه با مخلوط، وقتی غلظت‌ها و حدها بر حسب mg/m³ باشند.
 */
final readonly class MixtureExposureIndexMassV1 extends MixtureExposureIndex
{
    public function id(): string
    {
        return 'mixture-exposure-index-mass';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    protected function concentrationUnit(): Unit
    {
        return Unit::MilligramPerCubicMetre;
    }
}
