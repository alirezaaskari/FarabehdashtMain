<?php

declare(strict_types=1);

namespace App\Modules\Reports\Actions;

use App\Contracts\SettingsStore;
use App\Modules\Reports\Events\ReportPriceChanged;
use App\Modules\Reports\Services\ReportSale;
use App\Support\Money;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

/**
 * تغییر قیمت خرید تکی گزارش از پنل. خریدهای گذشته قیمت خودشان را دارند.
 */
final readonly class UpdateReportPrice
{
    public function __construct(
        private SettingsStore $settings,
        private ReportSale $sale,
        private Dispatcher $events,
    ) {}

    public function handle(Money $price, int $actorId): void
    {
        if ($price->isZero()) {
            throw new InvalidArgumentException('قیمت صفر یعنی صدور رایگان؛ برای بستن فروش، کلید «تک‌فروشی گزارش» را خاموش کنید.');
        }

        $before = $this->sale->price();

        if ($before->toman === $price->toman) {
            return;
        }

        $this->settings->set(ReportSale::PRICE_KEY, $price->toman, 'reports', 'قیمت خرید تکی صدور یک گزارش (تومان)');

        $this->events->dispatch(new ReportPriceChanged($before, $price, $actorId));
    }
}
