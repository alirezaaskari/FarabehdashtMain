<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Chemical;

use Farabehdasht\CalcEngine\Unit;

/**
 * میانگین وزنی-زمانی برای غلظت بر حسب ppm.
 */
final readonly class TwaPpmV1 extends TimeWeightedAverage
{
    public function id(): string
    {
        return 'twa-ppm';
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
