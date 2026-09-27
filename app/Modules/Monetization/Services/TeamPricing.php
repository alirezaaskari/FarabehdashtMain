<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Modules\Monetization\Domain\Enums\BillingCycle;
use App\Support\Money;
use Illuminate\Contracts\Config\Repository;

/**
 * قیمت تیم (DEC-61): قیمت واحد از پله تعداد صندلی، ضربدر صندلی و ماه‌های
 * صورتحساب. دوره سالانه ۱۲ ماه دسترسی می‌دهد و ۱۰ ماه صورتحساب می‌شود.
 */
final readonly class TeamPricing
{
    public function __construct(private Repository $config) {}

    public function minSeats(): int
    {
        return (int) $this->config->get('monetization.team.min_seats', 3);
    }

    public function maxSeats(): int
    {
        return (int) $this->config->get('monetization.team.max_seats', 50);
    }

    public function unitPrice(int $seats): Money
    {
        $price = 0;

        foreach ((array) $this->config->get('monetization.team.tiers', []) as $tier) {
            if ($seats >= (int) $tier['from']) {
                $price = (int) $tier['unit_price_toman'];
            }
        }

        return Money::toman($price);
    }

    public function billedMonths(BillingCycle $cycle): int
    {
        return $cycle === BillingCycle::Yearly
            ? (int) $this->config->get('monetization.team.yearly_billed_months', 10)
            : $cycle->months();
    }

    public function total(int $seats, BillingCycle $cycle): Money
    {
        return Money::toman($this->unitPrice($seats)->toman * $seats * $this->billedMonths($cycle));
    }

    /**
     * پله‌ها برای جدول قیمت صفحه خرید.
     *
     * @return list<array{from: int, unit: Money}>
     */
    public function tiers(): array
    {
        return array_values(array_map(
            static fn (array $tier): array => ['from' => (int) $tier['from'], 'unit' => Money::toman((int) $tier['unit_price_toman'])],
            (array) $this->config->get('monetization.team.tiers', []),
        ));
    }
}
