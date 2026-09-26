<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Services;

use App\Contracts\CommissionCalculator;
use App\Support\Bundles\BundleComponent;
use App\Support\Money;
use InvalidArgumentException;

/**
 * تقسیم قیمت بسته میان اجزا و سپس میان صاحب هر جزء و پلتفرم.
 *
 * سهم هر جزء به نسبت قیمت تکی‌اش است، پس تخفیف بسته به نسبت از همه اجزا
 * کم می‌شود و صاحب هیچ جزئی بیش از بقیه تخفیف نمی‌دهد. گردکردن با روش
 * «بزرگ‌ترین باقی‌مانده» است تا جمع سهم‌ها دقیقاً قیمت بسته شود. کمیسیون
 * از همان {@see CommissionCalculator} و با جریان خود جزء حساب می‌شود.
 */
final readonly class PriceSplitter
{
    public function __construct(private ?CommissionCalculator $commission) {}

    /**
     * @param  list<BundleComponent>  $components
     * @return list<array{component: BundleComponent, allocated: Money, owner: Money, platform: Money}>
     */
    public function split(Money $price, array $components): array
    {
        $total = array_sum(array_map(static fn (BundleComponent $c): int => $c->listPrice->toman, $components));

        if ($components === [] || $total <= 0) {
            throw new InvalidArgumentException('بسته بدون جزء قیمت‌دار تقسیم نمی‌شود.');
        }

        $floors = [];
        $remainders = [];

        foreach ($components as $i => $component) {
            $exact = $price->toman * $component->listPrice->toman;
            $floors[$i] = intdiv($exact, $total);
            $remainders[$i] = $exact % $total;
        }

        arsort($remainders);

        foreach (array_slice(array_keys($remainders), 0, $price->toman - array_sum($floors)) as $i) {
            $floors[$i]++;
        }

        $lines = [];

        foreach ($components as $i => $component) {
            $allocated = Money::toman($floors[$i]);
            $owner = Money::zero();

            if ($component->ownerUserId !== null && $component->commissionFlow !== null && $this->commission !== null) {
                $owner = $this->commission->split($allocated, $component->commissionFlow)->vendorAmount;
            }

            $lines[] = [
                'component' => $component,
                'allocated' => $allocated,
                'owner' => $owner,
                'platform' => $allocated->minus($owner),
            ];
        }

        return $lines;
    }
}
