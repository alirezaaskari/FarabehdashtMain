<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Contracts\QuotaCounter;
use App\Models\User;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Support\Entitlement\Feature;

/**
 * چند محاسبه این کاربر ذخیره کرده — ورودی لایه دسترسی برای سقف پلن رایگان.
 *
 * شمارش این‌جاست و نه در ماژول درآمدزایی، چون `SavedCalculation` مال همین
 * ماژول است (قاعده ۱). ماژول درآمدزایی فقط عدد را می‌گیرد.
 */
final readonly class SavedCalculationQuota implements QuotaCounter
{
    public function feature(): Feature
    {
        return Feature::SaveCalculation;
    }

    public function countFor(User $user): int
    {
        return SavedCalculation::query()->forUser((int) $user->getKey())->count();
    }
}
