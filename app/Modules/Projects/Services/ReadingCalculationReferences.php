<?php

declare(strict_types=1);

namespace App\Modules\Projects\Services;

use App\Contracts\CalculationReferences;
use App\Modules\Projects\Domain\ProjectReading;

/** قرائتی که از یک محاسبه ذخیره‌شده ساخته شده، آن محاسبه را نگه می‌دارد. */
final readonly class ReadingCalculationReferences implements CalculationReferences
{
    public function references(string $calculationUuid): bool
    {
        return ProjectReading::query()->where('calculation_uuid', $calculationUuid)->exists();
    }
}
