<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Contracts\SalesSwitch;
use App\Modules\Monetization\Domain\Enums\RevenueStream;

/**
 * پاسخ کلیدهای درآمدزایی به فروشگاه و دوره‌ها.
 *
 * نام ناشناخته باز حساب می‌شود: کلیدی که این ماژول نمی‌شناسد، کلیدی نیست
 * که مدیر خاموشش کرده باشد.
 */
final readonly class StreamSalesSwitch implements SalesSwitch
{
    public function __construct(private StreamRegistry $streams) {}

    public function isOpen(string $stream): bool
    {
        $known = RevenueStream::tryFrom($stream);

        return $known === null || $this->streams->isEnabled($known);
    }
}
