<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Models\User;
use App\Modules\Commerce\Actions\AddProductVersion;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\OrderItem;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Domain\Refund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * دانلود امضاشده — امضا به‌تنهایی کافی نیست، مالکیت هم دوباره بررسی می‌شود.
 */
final class DownloadAccessTest extends TestCase
{
    use RefreshDatabase;

    private function productWithVersion(): Product
    {
        $vendor = User::factory()->create();

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => $vendor->id,
            'slug' => 'quiet-template',
            'title' => 'قالب گزارش',
            'price_toman' => 100_000,
            'status' => ProductStatus::Published,
        ])->refresh();

        Storage::fake('local');
        $this->app->make(AddProductVersion::class)->handle(
            $product,
            '1.0.0',
            null,
            UploadedFile::fake()->create('template.zip', 50),
        );

        return $product->refresh();
    }

    private function signedUrl(Product $product): string
    {
        return URL::temporarySignedRoute('commerce.download', now()->addMinutes(10), ['product' => $product->id]);
    }

    public function test_a_guest_cannot_download(): void
    {
        $product = $this->productWithVersion();

        $this->get($this->signedUrl($product))->assertRedirect(route('login'));
    }

    public function test_a_buyer_without_a_paid_order_cannot_download(): void
    {
        $product = $this->productWithVersion();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get($this->signedUrl($product))->assertForbidden();
    }

    public function test_the_product_page_offers_a_buyer_a_fresh_download_link(): void
    {
        // پیوند صفحه پرداخت ۱۵ دقیقه عمر دارد؛ خریدار باید بعداً هم دانلود کند.
        $product = $this->productWithVersion();
        $buyer = User::factory()->create();
        $this->payFor($product, $buyer);

        $this->actingAs($buyer)->get(route('commerce.show', $product->slug))
            ->assertOk()
            ->assertSee('دانلود آخرین نسخه')
            ->assertDontSee('افزودن به سبد');
    }

    public function test_the_product_page_offers_others_the_cart(): void
    {
        $product = $this->productWithVersion();

        $this->actingAs(User::factory()->create())->get(route('commerce.show', $product->slug))
            ->assertOk()
            ->assertSee('افزودن به سبد')
            ->assertDontSee('دانلود آخرین نسخه');
    }

    public function test_a_buyer_with_a_paid_order_can_download(): void
    {
        $product = $this->productWithVersion();
        $buyer = User::factory()->create();
        $this->payFor($product, $buyer);

        $this->actingAs($buyer)->get($this->signedUrl($product))->assertOk();
    }

    public function test_a_link_without_a_valid_signature_is_rejected(): void
    {
        $product = $this->productWithVersion();
        $buyer = User::factory()->create();
        $this->payFor($product, $buyer);

        $this->actingAs($buyer)
            ->get(route('commerce.download', ['product' => $product->id]))
            ->assertForbidden();
    }

    public function test_a_fully_refunded_purchase_loses_download_access(): void
    {
        $product = $this->productWithVersion();
        $buyer = User::factory()->create();
        $item = $this->payFor($product, $buyer);

        Refund::query()->create([
            'uuid' => (string) Str::uuid7(),
            'order_item_id' => $item->id,
            'amount_toman' => $item->unit_price_toman,
        ]);

        $this->actingAs($buyer)->get($this->signedUrl($product))->assertForbidden();
    }

    private function payFor(Product $product, User $buyer): OrderItem
    {
        $order = Order::query()->create([
            'uuid' => (string) Str::uuid7(),
            'buyer_user_id' => $buyer->id,
            'status' => OrderStatus::Paid,
            'total_toman' => $product->price_toman,
            'paid_at' => now(),
        ]);

        return OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_user_id' => $product->vendor_user_id,
            'unit_price_toman' => $product->price_toman,
            'commission_rate_bp' => 2000,
            'commission_toman' => (int) round($product->price_toman * 0.2),
            'vendor_amount_toman' => $product->price_toman - (int) round($product->price_toman * 0.2),
        ]);
    }
}
