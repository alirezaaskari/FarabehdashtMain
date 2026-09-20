<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;

/**
 * ماده‌ای که منتظر تصمیم مدیر است.
 *
 * فقط پیش‌نویسی که دست‌کم یک حد مواجهه دارد در صف می‌آید: ماده‌ای که هنوز
 * هیچ داده‌ای ندارد، معطل تصمیم نیست، معطل ورود داده است.
 */
final readonly class PendingSubstances implements ApprovalQueueSource
{
    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $url = Route::has('filament.fbh.pages.chemicals-review')
            ? route('filament.fbh.pages.chemicals-review')
            : url('/');

        $drafts = Substance::query()
            ->where('status', SubstanceStatus::Draft->value)
            ->whereHas('limits')
            ->cursor();

        foreach ($drafts as $substance) {
            yield new PendingItem(
                ability: 'admin.chemicals.manage',
                kind: 'substance',
                title: 'انتشار ماده — '.$substance->name_fa,
                url: $url,
                waitingSince: $substance->updated_at,
            );
        }
    }
}
