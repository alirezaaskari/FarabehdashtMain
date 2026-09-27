<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Listeners;

use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Identity\Events\ProfileApproved;
use App\Modules\Identity\Events\ProfileDeactivated;
use Illuminate\Support\Carbon;

/**
 * صفحه عمومی فقط تا وقتی دیده می‌شود که نقش مشاور کاربر فعال است. با
 * غیرفعال‌شدن نقش پنهان می‌شود و با تأیید دوباره برمی‌گردد؛ چیزی پاک نمی‌شود.
 */
final readonly class SyncConsultantVisibility
{
    /** نقش‌هایی که صفحه عمومی دارند (مشاور، و آزمایشگاه از بخش ۱۹-۵). */
    private const TYPES = ['consultant', 'laboratory'];

    public function handle(ProfileApproved|ProfileDeactivated $event): void
    {
        if (! in_array($event->profile->type->value, self::TYPES, true)) {
            return;
        }

        ConsultantProfile::query()
            ->where('user_id', $event->profile->user_id)
            ->where('kind', $event->profile->type->value)
            ->update(['hidden_at' => $event instanceof ProfileDeactivated ? Carbon::now() : null]);
    }
}
