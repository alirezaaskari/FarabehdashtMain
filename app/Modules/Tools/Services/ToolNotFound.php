<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use RuntimeException;

final class ToolNotFound extends RuntimeException
{
    public static function slug(string $slug): self
    {
        return new self(sprintf('ابزاری با شناسه «%s» تعریف نشده است.', $slug));
    }
}
