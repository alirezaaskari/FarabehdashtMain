<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Bundles;

use App\Contracts\BundleComponentSource;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\OrderItem;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Services\ProductAccess;
use App\Support\Bundles\BundleComponent;
use App\Support\Payments\PaymentSource;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * فایل فروشگاه به‌عنوان جزء بسته راه‌حل.
 *
 * اعطا یک سفارش پرداخت‌شده با مبلغ صفر و منبع «بسته» می‌سازد: دسترسی دانلود
 * و «خریدهای من» از همان سفارش می‌آیند و پول در خرید بسته ثبت شده است.
 */
final readonly class ProductComponents implements BundleComponentSource
{
    public function __construct(private ProductAccess $access) {}

    public function kind(): string
    {
        return 'product';
    }

    public function label(): string
    {
        return 'فایل فروشگاه';
    }

    public function options(): array
    {
        return Product::query()->published()->orderBy('title')->get()
            ->map(fn (Product $product): BundleComponent => $this->component($product))
            ->values()
            ->all();
    }

    public function find(string $ref): ?BundleComponent
    {
        $product = Product::query()->published()->find((int) $ref);

        return $product === null ? null : $this->component($product);
    }

    public function owns(int $userId, string $ref): bool
    {
        $product = Product::query()->find((int) $ref);

        return $product !== null && $this->access->userOwns($userId, $product);
    }

    public function grant(int $userId, string $ref, string $purchaseUuid): void
    {
        $product = Product::query()->findOrFail((int) $ref);

        if ($this->access->userOwns($userId, $product)) {
            return;
        }

        $order = Order::query()->create([
            'uuid' => (string) Str::uuid7(),
            'buyer_user_id' => $userId,
            'status' => OrderStatus::Paid,
            'total_toman' => 0,
            'payment_source' => PaymentSource::Bundle,
            'paid_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_user_id' => $product->vendor_user_id,
            'unit_price_toman' => 0,
            'discount_toman' => 0,
            'commission_rate_bp' => 0,
            'commission_toman' => 0,
            'vendor_amount_toman' => 0,
        ]);
    }

    public function revoke(int $userId, string $ref): void
    {
        Order::query()
            ->where('buyer_user_id', $userId)
            ->where('status', OrderStatus::Paid->value)
            ->where('payment_source', PaymentSource::Bundle->value)
            ->whereHas('items', fn ($items) => $items->where('product_id', (int) $ref))
            ->update(['status' => OrderStatus::Refunded->value]);
    }

    private function component(Product $product): BundleComponent
    {
        return new BundleComponent(
            kind: $this->kind(),
            ref: (string) $product->id,
            title: $product->title,
            listPrice: $product->price(),
            ownerUserId: $product->vendor_user_id,
            commissionFlow: 'shop',
            url: Route::has('commerce.show') ? route('commerce.show', $product->slug) : null,
        );
    }
}
