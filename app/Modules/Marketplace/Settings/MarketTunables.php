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
            new Tunable('marketplace.bids.milestones_max', self::SECTION, 'حداکثر مرحله در هر پیشنهاد', TunableUnit::Count, 1, 10),
            new Tunable('marketplace.bids.milestone_min_toman', self::SECTION, 'کمینه مبلغ هر مرحله', TunableUnit::Toman, 100_000, 50_000_000),
            new Tunable('marketplace.bids.per_30_days', self::SECTION, 'سقف پیشنهاد هر مجری در ۳۰ روز', TunableUnit::Count, 1, 500),
            new Tunable('marketplace.contracts.pay_days', self::SECTION, 'مهلت پرداخت مرحله اول پس از پذیرش', TunableUnit::Days, 1, 30),
            new Tunable('marketplace.contracts.auto_release_days', self::SECTION, 'آزادسازی خودکار پس از تحویل بی‌پاسخ', TunableUnit::Days, 2, 30),
            new Tunable('marketplace.contracts.revisions_max', self::SECTION, 'حداکثر درخواست اصلاح در هر مرحله', TunableUnit::Count, 0, 5),
            new Tunable('marketplace.contracts.cancel_grace_days', self::SECTION, 'لغو کارفرما پس از گذشتن مهلت تحویل', TunableUnit::Days, 1, 30),
            new Tunable('marketplace.ratings.window_days', self::SECTION, 'مهلت امتیازدادن پس از پایان قرارداد', TunableUnit::Days, 3, 90),
            new Tunable('marketplace.ratings.reveal_days', self::SECTION, 'نمایش امتیاز بی‌پاسخ طرف دیگر پس از', TunableUnit::Days, 1, 60),
            new Tunable('marketplace.ratings.average_min', self::SECTION, 'کمینه امتیاز برای نمایش میانگین مجری', TunableUnit::Count, 1, 20),
            new Tunable('marketplace.ratings.trusted_client_min', self::SECTION, 'پروژه پرداخت‌شده برای نشان کارفرمای خوش‌حساب', TunableUnit::Count, 1, 50),
            new Tunable('marketplace.messages.strikes_limit', self::SECTION, 'اخطار پیام تا بسته‌شدن دسترسی', TunableUnit::Count, 1, 20),
        ];
    }
}
