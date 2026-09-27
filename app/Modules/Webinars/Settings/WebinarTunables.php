<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Settings;

use App\Contracts\TunableSource;
use App\Support\Settings\Tunable;
use App\Support\Settings\TunableUnit;

final readonly class WebinarTunables implements TunableSource
{
    private const SECTION = 'رویداد و وبینار';

    public function tunables(): array
    {
        return [
            new Tunable('webinars.hold_minutes', self::SECTION, 'نگه‌داشتن جای ثبت‌نام تا پایان پرداخت', TunableUnit::Minutes, 5, 120),
            new Tunable('webinars.join_opens_minutes', self::SECTION, 'باز شدن پیوند ورود، پیش از شروع', TunableUnit::Minutes, 5, 240),
            new Tunable('webinars.remind_before_minutes', self::SECTION, 'یادآور رویداد، پیش از شروع', TunableUnit::Minutes, 15, 2_880),
        ];
    }
}
