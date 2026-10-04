<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Settings;

use App\Contracts\TunableSource;
use App\Support\Settings\Tunable;
use App\Support\Settings\TunableUnit;

/** حدهای بانک مواد که مدیر ارشد از پنل عوض می‌کند. */
final readonly class ChemicalsTunables implements TunableSource
{
    private const SECTION = 'بانک مواد شیمیایی';

    public function tunables(): array
    {
        return [
            new Tunable('chemicals.reports.per_day', self::SECTION, 'سقف گزارش اشتباه هر کاربر در روز', TunableUnit::Count, 1, 100),
        ];
    }
}
