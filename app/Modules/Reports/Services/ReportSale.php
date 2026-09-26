<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Contracts\SalesSwitch;
use App\Contracts\SettingsStore;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Domain\ReportPurchase;
use App\Support\Money;
use Illuminate\Contracts\Container\Container;

/**
 * تک‌فروشی صدور گزارش (بخش ۱۸-۵، DEC-44).
 *
 * سه پرسش را یک‌جا جواب می‌دهد: فروش باز است (کلید «تک‌فروشی گزارش» در
 * کلیدهای درآمدزایی)، قیمت امروز چند است (تنظیم مدیر، با پیش‌فرض config) و
 * آیا این پیش‌نویس از پیش خریده شده.
 *
 * خرید، اصلاح‌های بعدی همان گزارش را هم پوشش می‌دهد: نسخه تازه همان منبع
 * است و کارشناسی که غلط تایپی را درست می‌کند نباید دوباره بپردازد.
 */
final readonly class ReportSale
{
    public const PRICE_KEY = 'reports.single_price_toman';

    /** سقف پیمایش زنجیره اصلاح؛ فقط محافظ در برابر داده خراب. */
    private const MAX_REVISIONS = 100;

    public function __construct(
        private Container $container,
        private SalesSwitch $switch,
        private int $defaultPriceToman,
    ) {}

    public function isOpen(): bool
    {
        return $this->switch->isOpen(SalesSwitch::REPORT_SALE) && ! $this->price()->isZero();
    }

    public function price(): Money
    {
        $toman = $this->container->bound(SettingsStore::class)
            ? $this->container->make(SettingsStore::class)->integer(self::PRICE_KEY, $this->defaultPriceToman)
            : $this->defaultPriceToman;

        return Money::toman(max(0, $toman));
    }

    public function covers(Report $report): bool
    {
        return ReportPurchase::query()
            ->paid()
            ->whereIn('report_id', $this->lineage($report))
            ->exists();
    }

    /**
     * شناسه خود گزارش و همه نسخه‌هایی که پیش از آن آمده‌اند.
     *
     * @return list<int>
     */
    private function lineage(Report $report): array
    {
        $ids = [$report->id];
        $previous = $report->supersedes_id;

        while ($previous !== null && count($ids) < self::MAX_REVISIONS) {
            $ids[] = $previous;
            $parent = Report::query()->whereKey($previous)->value('supersedes_id');
            $previous = $parent === null ? null : (int) $parent;
        }

        return $ids;
    }
}
