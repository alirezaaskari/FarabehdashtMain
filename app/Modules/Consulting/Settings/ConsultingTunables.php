<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Settings;

use App\Contracts\TunableSource;
use App\Support\Settings\Tunable;
use App\Support\Settings\TunableUnit;

/** مهلت‌ها و حد قیمت خدمت مشاوره و بررسی گزارش (DEC-53، DEC-54، DEC-57). */
final readonly class ConsultingTunables implements TunableSource
{
    private const SECTION = 'مشاوره و بررسی گزارش';

    public function tunables(): array
    {
        return [
            new Tunable('consulting.orders.reply_hours', self::SECTION, 'مهلت پذیرش یا رد درخواست توسط مشاور', TunableUnit::Hours, 12, 168, 'پس از آن پول کامل به کیف پول خریدار برمی‌گردد'),
            new Tunable('consulting.orders.auto_release_days', self::SECTION, 'آزادسازی خودکار پول پس از «انجام شد»', TunableUnit::Days, 1, 30, 'اگر خریدار نه تأیید کند نه اعتراض'),
            new Tunable('consulting.services.price_min_toman', self::SECTION, 'کمترین قیمت خدمت مشاوره', TunableUnit::Toman, 10_000, 10_000_000),
            new Tunable('consulting.services.price_max_toman', self::SECTION, 'بیشترین قیمت خدمت مشاوره', TunableUnit::Toman, 100_000, 500_000_000),
            new Tunable('consulting.reviews.price_min_toman', self::SECTION, 'کمترین قیمت بررسی گزارش', TunableUnit::Toman, 10_000, 10_000_000),
            new Tunable('consulting.reviews.due_days', self::SECTION, 'مهلت تحویل بررسی گزارش پس از پذیرش', TunableUnit::Days, 1, 30),
        ];
    }
}
