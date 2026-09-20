<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Chemical;

use Farabehdasht\CalcEngine\Unit;

/**
 * میانگین وزنی-زمانی برای غلظت بر حسب میلی‌گرم بر مترمکعب.
 */
final readonly class TwaMassConcentrationV1 extends TimeWeightedAverage
{
    public function id(): string
    {
        return 'twa-mass-concentration';
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
