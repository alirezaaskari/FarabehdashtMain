<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Exception;

use Farabehdasht\CalcEngine\Unit;
use LogicException;

final class UnitMismatch extends LogicException implements CalcEngineException
{
    public static function between(Unit $expected, Unit $actual): self
    {
        return new self(sprintf(
            'واحد انتظار می‌رفت «%s» باشد ولی «%s» داده شد.',
            $expected->label(),
            $actual->label(),
        ));
    }
}
