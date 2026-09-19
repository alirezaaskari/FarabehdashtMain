<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Contracts\ApprovalQueueSource;
use App\Models\User;
use App\Support\Admin\PendingItem;

/**
 * صف یکپارچه تأیید.
 *
 * هر مدیر فقط مواردی را می‌بیند که توانایی تصمیم‌گیری درباره‌شان را دارد:
 * مدیر محتوا صف تسویه مالی را اصلاً نمی‌بیند و برعکس. فیلتر همین‌جاست تا هر
 * جای نمایشی که بعداً اضافه شود، خودبه‌خود همین قاعده را رعایت کند.
 */
final readonly class ApprovalQueue
{
    /** @param  iterable<ApprovalQueueSource>  $sources */
    public function __construct(
        private iterable $sources,
        private AdminAccess $access,
    ) {}

    /** @return list<PendingItem> */
    public function for(User $admin): array
    {
        $abilities = $this->access->abilitiesOf($admin);
        $items = [];

        foreach ($this->sources as $source) {
            foreach ($source->pendingItems() as $item) {
                if (in_array($item->ability, $abilities, strict: true)) {
                    $items[] = $item;
                }
            }
        }

        // قدیمی‌ترین معطلی اول: چیزی که بیشتر منتظر مانده فوری‌تر است.
        usort($items, static fn (PendingItem $a, PendingItem $b): int => ($a->waitingSince?->getTimestamp() ?? PHP_INT_MAX)
            <=> ($b->waitingSince?->getTimestamp() ?? PHP_INT_MAX));

        return $items;
    }

    /** @return array<string, int> شمارش به تفکیک نوع */
    public function countsByKind(User $admin): array
    {
        $counts = [];

        foreach ($this->for($admin) as $item) {
            $counts[$item->kind] = ($counts[$item->kind] ?? 0) + 1;
        }

        return $counts;
    }
}
