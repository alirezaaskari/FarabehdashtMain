<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * فایل‌هایی که یک کاربر خریده و هنوز حق دانلودشان را دارد.
 *
 * همان ملاک `ProductAccess`: سفارش پرداخت‌شده یا جزئی بازگشتی، و ردیفی که
 * کامل بازگشت نخورده. محصولی که دو بار خریده شده یک بار نشان داده می‌شود.
 */
final readonly class BuyerPurchases
{
    /** @return Collection<int, OrderItem> */
    public function for(int $userId): Collection
    {
        return OrderItem::query()
            ->whereHas('order', static function (Builder $order) use ($userId): void {
                $order->where('buyer_user_id', $userId)
                    ->whereIn('status', [OrderStatus::Paid->value, OrderStatus::PartiallyRefunded->value]);
            })
            ->with(['order', 'product.versions'])
            ->latest('id')
            ->get()
            ->reject(static fn (OrderItem $item): bool => $item->isFullyRefunded())
            ->unique('product_id')
            ->values();
    }
}
