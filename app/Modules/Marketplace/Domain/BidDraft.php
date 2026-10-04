<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

/**
 * پیشنهاد فرستاده‌شده پس از اعتبارسنجی فرم: متن و ۱ تا ۵ مرحله.
 */
final readonly class BidDraft
{
    /** @param  list<array{title: string, amount_toman: int, days: int}>  $milestones */
    public function __construct(
        public string $cover,
        public array $milestones,
    ) {}

    public function totalToman(): int
    {
        return array_sum(array_column($this->milestones, 'amount_toman'));
    }

    public function totalDays(): int
    {
        return array_sum(array_column($this->milestones, 'days'));
    }
}
