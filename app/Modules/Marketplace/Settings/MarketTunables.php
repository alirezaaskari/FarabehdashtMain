<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Settings;

use App\Contracts\TunableSource;
use App\Support\Settings\Tunable;
use App\Support\Settings\TunableUnit;

/** حدها و مهلت‌های بازار پروژه (DEC-79 و بعدی‌ها). */
final readonly class MarketTunables implements TunableSource
{
    private const SECTION = 'بازار پروژه';

    public function tunables(): array
    {
        return [
            new Tunable('marketplace.projects.budget_min_toman', self::SECTION, 'کمینه بودجه پروژه', TunableUnit::Toman, 100_000, 100_000_000),
            new Tunable('marketplace.projects.bid_days', self::SECTION, 'مهلت دریافت پیشنهاد پس از انتشار', TunableUnit::Days, 3, 60),
        ];
    }
}
