<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Exception;

use LogicException;

final class DuplicateFormula extends LogicException implements CalcEngineException
{
    public static function of(string $formulaId, string $version): self
    {
        return new self(sprintf(
            'فرمول «%s» نسخه «%s» قبلاً ثبت شده است. تغییر رفتار یک فرمول باید نسخه تازه بگیرد، نه این‌که نسخه موجود را بازنویسی کند.',
            $formulaId,
            $version,
        ));
    }
}
