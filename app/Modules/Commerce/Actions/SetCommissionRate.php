<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\CommissionRate;
use App\Modules\Commerce\Events\CommissionRateChanged;
use App\Modules\Commerce\Services\CommissionService;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * نرخ تازه کمیسیون یک جریان، از امروز یا تاریخی در آینده.
 *
 * نرخ‌ها فقط‌افزودنی‌اند؛ هر فروش نرخ لحظه خودش را Snapshot کرده، پس نرخ
 * تازه هیچ فروش گذشته‌ای را جابه‌جا نمی‌کند (ADR-0003).
 */
final readonly class SetCommissionRate
{
    public const MAX_BP = 5000;

    public function __construct(
        private CommissionService $rates,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function handle(string $flow, int $rateBp, Carbon $effectiveFrom, int $actorId): CommissionRate
    {
        if (! array_key_exists($flow, (array) $this->config->get('commerce.commission.default_rate_bp', []))) {
            throw new InvalidArgumentException('این جریان فروش شناخته نیست.');
        }

        if ($rateBp < 0 || $rateBp > self::MAX_BP) {
            throw new InvalidArgumentException('نرخ باید بین صفر و ۵۰ درصد باشد.');
        }

        if ($effectiveFrom->isBefore(Carbon::today())) {
            throw new InvalidArgumentException('تاریخ اثر نمی‌تواند گذشته باشد؛ فروش‌های گذشته نرخ خودشان را دارند.');
        }

        $before = $this->rates->currentRateBp($flow);

        $rate = CommissionRate::query()->create([
            'flow' => $flow,
            'rate_bp' => $rateBp,
            'effective_from' => $effectiveFrom->toDateString(),
            'created_by' => $actorId,
        ]);

        $this->events->dispatch(new CommissionRateChanged($flow, $before, $rateBp, $effectiveFrom->toDateString(), $actorId));

        return $rate;
    }
}
