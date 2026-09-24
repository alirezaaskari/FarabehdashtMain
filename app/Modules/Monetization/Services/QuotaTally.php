<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Contracts\QuotaCounter;
use App\Models\User;
use App\Support\Entitlement\Feature;

/**
 * چند مورد از هر امکان مصرف شده — جمع‌زننده شمارنده‌های ثبت‌شده ماژول‌ها.
 *
 * این ماژول نمی‌داند `SavedCalculation` یا `Project` وجود دارند و حق هم
 * ندارد بداند (قاعده ۱). هر ماژول شمارنده‌اش را با برچسب کانتینر ثبت
 * می‌کند و این‌جا فقط جمع زده می‌شود.
 *
 * `null` یعنی «هیچ شمارنده‌ای برای این امکان ثبت نشده»، نه صفر: امکانی که
 * ماژولش خاموش است نباید سقف بخورد.
 */
final readonly class QuotaTally
{
    /** @param  iterable<QuotaCounter>  $counters */
    public function __construct(private iterable $counters) {}

    public function countFor(User $user, Feature $feature): ?int
    {
        $total = null;

        foreach ($this->counters as $counter) {
            if ($counter->feature() !== $feature) {
                continue;
            }

            $total = ($total ?? 0) + $counter->countFor($user);
        }

        return $total;
    }
}
