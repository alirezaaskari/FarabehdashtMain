<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\OrderItem;
use App\Modules\Commerce\Domain\Product;

/**
 * آیا یک کاربر حق دانلود یک محصول را دارد.
 *
 * ملاک، سفارش است نه یک جدول مجوز جدا: هر ردیف سفارش که سفارشش پرداخت‌شده
 * یا جزئی بازگشتی باشد و خودش کامل بازگشت نخورده باشد، دسترسی می‌دهد.
 * فروشنده و مدیر از مسیر دیگری (توانایی خودشان) دسترسی می‌گیرند، نه این سرویس.
 */
final readonly class ProductAccess
{
    public function userOwns(int $userId, Product $product): bool
    {
        return OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas(
                'order',
                fn ($query) => $query
                    ->where('buyer_user_id', $userId)
                    ->whereIn('status', [OrderStatus::Paid->value, OrderStatus::PartiallyRefunded->value]),
            )
            ->get()
            ->contains(fn (OrderItem $item): bool => ! $item->isFullyRefunded());
    }
}
