<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Enums;

/**
 * مسیر ورود ماده به بدن.
 */
enum ExposureRoute: string
{
    case Inhalation = 'inhalation';
    case Skin = 'skin';
    case Eye = 'eye';
    case Ingestion = 'ingestion';

    public function label(): string
    {
        return match ($this) {
            self::Inhalation => 'استنشاق',
            self::Skin => 'پوستی',
            self::Eye => 'چشمی',
            self::Ingestion => 'گوارشی',
        };
    }
}
