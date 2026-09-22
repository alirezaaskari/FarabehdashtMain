<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;

/**
 * محصولی که منتظر تصمیم مدیر است.
 */
final readonly class PendingProducts implements ApprovalQueueSource
{
    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $url = Route::has('filament.fbh.pages.commerce-review')
            ? route('filament.fbh.pages.commerce-review')
            : url('/');

        $pending = Product::query()->where('status', ProductStatus::InReview->value)->cursor();

        foreach ($pending as $product) {
            yield new PendingItem(
                ability: 'admin.content.review',
                kind: 'product',
                title: 'انتشار محصول — '.$product->title,
                url: $url,
                waitingSince: $product->updated_at,
            );
        }
    }
}
