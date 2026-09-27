<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Settings;

use App\Contracts\TunableSource;
use App\Support\PersianNumber;
use App\Support\Settings\Tunable;
use App\Support\Settings\TunableUnit;
use Illuminate\Contracts\Config\Repository;

/**
 * عددهای اشتراک و تیم که مدیر ارشد از پنل عوض می‌کند. قیمت خود پلن‌های حرفه‌ای
 * در جدول پلن‌هاست و صفحه «اشتراک‌ها» را دارد.
 */
final readonly class MonetizationTunables implements TunableSource
{
    private const SECTION = 'اشتراک حرفه‌ای و تیم';

    public function __construct(private Repository $config) {}

    public function tunables(): array
    {
        $tunables = [];

        foreach (array_keys((array) $this->config->get('monetization.team.tiers', [])) as $index) {
            $from = (int) $this->config->get('monetization.team.tiers.'.$index.'.from');

            $tunables[] = new Tunable(
                key: 'monetization.team.tiers.'.$index.'.unit_price_toman',
                section: self::SECTION,
                label: 'قیمت هر صندلی تیم در ماه، از '.PersianNumber::format($from).' نفر',
                unit: TunableUnit::Toman,
                min: 10_000,
                max: 10_000_000,
                hint: 'فقط خریدهای تازه تیم؛ تیم پرداخت‌شده تا پایان دوره‌اش همان می‌ماند',
            );
        }

        return [
            ...$tunables,
            new Tunable('monetization.team.min_seats', self::SECTION, 'کمترین تعداد صندلی تیم', TunableUnit::Count, 3, 20, 'صاحب تیم خودش یک صندلی است'),
            new Tunable('monetization.team.yearly_billed_months', self::SECTION, 'بهای تیم سالانه برابر چند ماه', TunableUnit::Months, 6, 12, 'دسترسی سالانه همیشه ۱۲ ماه است'),
            new Tunable('monetization.pro_discount_percent', self::SECTION, 'تخفیف مشترک حرفه‌ای روی فایل‌های فروشگاه', TunableUnit::Percent, 0, 50, 'از سهم کمیسیون سایت کم می‌شود'),
            new Tunable('monetization.ending_reminder_days', self::SECTION, 'یادآور پایان اشتراک، چند روز مانده', TunableUnit::Days, 1, 30),
        ];
    }
}
