<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Modules\Commerce\Filament\Pages\PayoutRequestsPage;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;

/** درخواست تسویه‌ای که منتظر واریز یا رد مدیر مالی است. */
final readonly class PendingPayouts implements ApprovalQueueSource
{
    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $url = Route::has('filament.fbh.pages.payout-requests')
            ? route('filament.fbh.pages.payout-requests')
            : url('/');

        foreach (PayoutRequest::query()->open()->oldest('id')->cursor() as $payout) {
            yield new PendingItem(
                ability: PayoutRequestsPage::ABILITY,
                kind: 'settlement',
                title: 'درخواست تسویه — '.$payout->amount()->format(),
                url: $url,
                waitingSince: $payout->created_at,
            );
        }
    }
}
