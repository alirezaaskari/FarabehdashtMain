<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Services;

use App\Contracts\CommissionCalculator;
use App\Contracts\EscrowKeeper;
use App\Contracts\SalesSwitch;
use App\Support\Money;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;

/**
 * کلید درآمدی و نرخ کمیسیون بازار پروژه (DEC-75).
 *
 * کلید `project_market_commission` فقط پذیرش پیشنهاد تازه را می‌بندد؛
 * قرارداد جاری تا پایان پرداخت، تحویل و آزاد می‌شود تا پول امانی گیر نکند.
 */
final readonly class ContractTerms
{
    public const FLOW = 'project_market';

    public function __construct(
        private Container $container,
        private SalesSwitch $sales,
        private Repository $config,
    ) {}

    public function isOpen(): bool
    {
        return $this->container->bound(EscrowKeeper::class) && $this->sales->isOpen(SalesSwitch::PROJECT_MARKET);
    }

    /** نرخ امروز به Basis Point؛ روی قرارداد ثبت می‌شود و بعد عوض نمی‌شود. */
    public function rateBp(): int
    {
        if ($this->container->bound(CommissionCalculator::class)) {
            return $this->container->make(CommissionCalculator::class)->split(Money::toman(10_000), self::FLOW)->rateBp;
        }

        return (int) $this->config->get('marketplace.contracts.fallback_commission_bp', 1000);
    }
}
