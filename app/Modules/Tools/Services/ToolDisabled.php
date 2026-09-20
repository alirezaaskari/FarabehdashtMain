<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use RuntimeException;

/**
 * ابزاری که مدیر خاموشش کرده، محاسبه نمی‌کند — حتی اگر کسی مستقیم فرمش را
 * بفرستد.
 */
final class ToolDisabled extends RuntimeException
{
    public static function slug(string $slug): self
    {
        return new self(sprintf('ابزار «%s» در حال حاضر غیرفعال است.', $slug));
    }
}
