<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Chemical;

use Farabehdasht\CalcEngine\Unit;

/**
 * شاخص مواجهه با مخلوط، وقتی غلظت‌ها و حدها بر حسب ppm باشند.
 */
final readonly class MixtureExposureIndexPpmV1 extends MixtureExposureIndex
{
    public function id(): string
    {
        return 'mixture-exposure-index-ppm';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    protected function concentrationUnit(): Unit
    {
        return Unit::PartsPerMillion;
    }
}
