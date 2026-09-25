<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Models\User;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\OrderItem;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Domain\ProductVersion;
use App\Modules\Commerce\Domain\Refund;
use App\Modules\Commerce\Services\ProductVersionReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class ModelsSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_can_have_versions_and_an_order_can_be_partially_refunded(): void
    {
        $vendor = User::factory()->create();
        $buyer = User::factory()->create();

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => $vendor->id,
            'slug' => 'quiet-template',
            'title' => 'قالب گزارش سکوت‌سنجی',
            'price_toman' => 100_000,
            'status' => ProductStatus::Published,
        ]);

        ProductVersion::query()->create([
            'product_id' => $product->id,
            'version' => '1.0.0',
            'file_path' => 'products/quiet-template/v1.zip',
            'file_size' => 2048,
            'checksum' => hash('sha256', 'v1'),
        ]);

        $this->assertNull($product->latestVersion(), 'نسخه تأییدنشده به خریدار نمی‌رسد.');

        $this->app->make(ProductVersionReview::class)->approve($product);
        $product->unsetRelation('versions');

        $this->assertSame('1.0.0', $product->latestVersion()?->version);

        $order = Order::query()->create([
            'uuid' => (string) Str::uuid7(),
            'buyer_user_id' => $buyer->id,
            'status' => OrderStatus::Paid,
            'total_toman' => 100_000,
            'paid_at' => now(),
        ]);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_user_id' => $vendor->id,
            'unit_price_toman' => 100_000,
            'commission_rate_bp' => 2000,
            'commission_toman' => 20_000,
            'vendor_amount_toman' => 80_000,
        ]);

        $this->assertSame(100_000, $item->remainingRefundable()->toman);
        $this->assertFalse($item->isFullyRefunded());

        Refund::query()->create([
            'uuid' => (string) Str::uuid7(),
            'order_item_id' => $item->id,
            'amount_toman' => 30_000,
            'reason' => 'انصراف جزئی',
        ]);

        $this->assertSame(30_000, $item->refundedAmount()->toman);
        $this->assertSame(70_000, $item->remainingRefundable()->toman);
        $this->assertFalse($item->isFullyRefunded());
    }

    public function test_a_product_version_is_immutable(): void
    {
        $vendor = User::factory()->create();

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => $vendor->id,
            'slug' => 'immutable-check',
            'title' => 'محصول آزمایشی',
            'price_toman' => 1,
            'status' => ProductStatus::Draft,
        ]);

        $version = ProductVersion::query()->create([
            'product_id' => $product->id,
            'version' => '1.0.0',
            'file_path' => 'x.zip',
            'file_size' => 1,
            'checksum' => hash('sha256', 'x'),
        ]);

        $this->expectException(RuntimeException::class);
        $version->update(['version' => '2.0.0']);
    }
}
