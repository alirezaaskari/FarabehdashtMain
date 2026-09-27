<?php

declare(strict_types=1);

namespace App\Modules\Projects\Settings;

use App\Contracts\TunableSource;
use App\Support\Settings\Tunable;
use App\Support\Settings\TunableUnit;

final readonly class ProjectTunables implements TunableSource
{
    public function tunables(): array
    {
        return [
            new Tunable('projects.calibration_warning_days', 'پروژه و تجهیزات', 'هشدار و یادآور کالیبراسیون، چند روز مانده', TunableUnit::Days, 1, 180),
        ];
    }
}
