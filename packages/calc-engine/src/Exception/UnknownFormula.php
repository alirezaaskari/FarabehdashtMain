<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Exception;

use InvalidArgumentException;

final class UnknownFormula extends InvalidArgumentException implements CalcEngineException
{
    public static function id(string $formulaId): self
    {
        return new self(sprintf('فرمولی با شناسه «%s» ثبت نشده است.', $formulaId));
    }

    public static function version(string $formulaId, string $version): self
    {
        return new self(sprintf(
            'فرمول «%s» نسخه «%s» ندارد.',
            $formulaId,
            $version,
        ));
    }
}
