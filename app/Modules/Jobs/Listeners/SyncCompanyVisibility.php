<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Listeners;

use App\Modules\Identity\Events\ProfileApproved;
use App\Modules\Identity\Events\ProfileDeactivated;
use App\Modules\Jobs\Domain\Company;
use Illuminate\Support\Carbon;

/**
 * صفحه شرکت و آگهی‌هایش فقط تا وقتی دیده می‌شوند که نقش کارفرما فعال است.
 * با غیرفعال‌شدن نقش پنهان می‌شوند و با تأیید دوباره برمی‌گردند؛ چیزی پاک
 * نمی‌شود و روزهای اعتبار آگهی هم متوقف نمی‌شود.
 */
final readonly class SyncCompanyVisibility
{
    public function handle(ProfileApproved|ProfileDeactivated $event): void
    {
        if ($event->profile->type->value !== 'employer') {
            return;
        }

        Company::query()
            ->where('user_id', $event->profile->user_id)
            ->update(['hidden_at' => $event instanceof ProfileDeactivated ? Carbon::now() : null]);
    }
}
