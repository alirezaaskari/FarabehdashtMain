<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Contracts\FinancialGuard;

/** نگهبان مالی وقتی ماژول Admin نیست: بدون آن، مشاهده به‌عنوان کاربری هم نیست. */
final readonly class AlwaysAllowed implements FinancialGuard
{
    public function assertAllowed(): void {}
}
