<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Site;

use App\Contracts\ShelfSource;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\Enums\ProviderKind;

/** فهرست مشاوران فقط مشاور است؛ دایرکتوری خدمات آزمایشگاه‌ها را هم دارد. */
final readonly class ConsultingShelf implements ShelfSource
{
    public function shelves(): array
    {
        return [
            'consulting.index' => ConsultantProfile::query()->listed()->where('kind', ProviderKind::Consultant)->toBase(),
            'consulting.directory.index' => ConsultantProfile::query()->listed()->toBase(),
        ];
    }
}
