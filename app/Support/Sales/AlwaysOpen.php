<?php

declare(strict_types=1);

namespace App\Support\Sales;

use App\Contracts\SalesSwitch;

/** بدون ماژول درآمدزایی کلیدی نیست، پس همه فروش‌ها بازند. */
final readonly class AlwaysOpen implements SalesSwitch
{
    public function isOpen(string $stream): bool
    {
        return true;
    }
}
