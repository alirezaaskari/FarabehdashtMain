<?php

declare(strict_types=1);

namespace App\Support\Entitlement;

use App\Contracts\SubscriberDiscount;
use App\Models\User;

/**
 * بدون ماژول درآمدزایی، هیچ‌کس تخفیف مشترک ندارد.
 */
final readonly class NoDiscount implements SubscriberDiscount
{
    public function percentFor(User $user): float
    {
        return 0.0;
    }
}
