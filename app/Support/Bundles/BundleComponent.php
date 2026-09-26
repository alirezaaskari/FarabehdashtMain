<?php

declare(strict_types=1);

namespace App\Support\Bundles;

use App\Support\Money;

/**
 * یک جزء بسته با قیمت تکی امروزش.
 *
 * $ownerUserId صاحب سهم فروش است (فروشنده یا مدرس)؛ null یعنی درآمد خود
 * پلتفرم، مثل ماه‌های Pro. $commissionFlow همان شناسه جریان
 * `CommissionCalculator` است («shop»، «course»).
 */
final readonly class BundleComponent
{
    public function __construct(
        public string $kind,
        public string $ref,
        public string $title,
        public Money $listPrice,
        public ?int $ownerUserId = null,
        public ?string $commissionFlow = null,
        public ?string $url = null,
    ) {}

    public function key(): string
    {
        return $this->kind.':'.$this->ref;
    }
}
