<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Settings;

use App\Contracts\TunableSource;
use App\Support\Settings\Tunable;
use App\Support\Settings\TunableUnit;

final readonly class CommerceTunables implements TunableSource
{
    public function tunables(): array
    {
        return [
            new Tunable(
                key: 'commerce.payout.minimum_toman',
                section: 'تسویه',
                label: 'کمترین مبلغ درخواست تسویه',
                unit: TunableUnit::Toman,
                min: 10_000,
                max: 100_000_000,
                hint: 'برای فروشنده، مدرس و مشاور؛ درخواست‌های باز دست نمی‌خورند',
            ),
        ];
    }
}
