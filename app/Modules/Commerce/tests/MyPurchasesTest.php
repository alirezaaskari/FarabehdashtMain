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
use App\Modules\Commerce\Services\ProductVersionReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * «خریدهای من» — همه فایل‌های خریده‌شده، هرکدام با پیوند دانلود تازه.
 */
final class MyPurchasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('commerce.purchases'))->assertRedirect(route('login'));
    }

    public function test_a_new_buyer_sees_the_way_to_the_shop(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('commerce.purchases'))
            ->assertOk()
            ->assertSee('هنوز فایلی نخریده‌اید')
            ->assertSee(route('commerce.index'));
    }

    public function test_paid_purchases_are_listed_once_with_a_working_download_link(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('noise-template', 'قالب گزارش صدا');
        $this->payFor($product, $buyer);
        $this->payFor($product, $buyer);

        $response = $this->actingAs($buyer)->get(route('commerce.purchases'))
            ->assertOk()
            ->assertSee('قالب گزارش صدا')
            ->assertSee('1.0.0');

        preg_match_all('#href="([^"]+/download[^"]+)"#', (string) $response->getContent(), $links);
        $this->assertCount(1, $links[1]);

        $this->get(html_entity_decode($links[1][0]))->assertOk();
    }

    public function test_unpaid_refunded_and_other_buyers_purchases_are_left_out(): void
    {
        $buyer = User::factory()->create();

        $this->payFor($this->product('pending', 'سفارش پرداخت‌نشده'), $buyer, OrderStatus::Pending);

        $refunded = $this->payFor($this->product('refunded', 'خرید بازگشتی'), $buyer);
        Refund::query()->create([
            'uuid' => (string) Str::uuid7(),
            'order_item_id' => $refunded->id,
            'amount_toman' => $refunded->unit_price_toman,
        ]);

        $this->payFor($this->product('someone-else', 'خرید دیگری'), User::factory()->create());

        $this->actingAs($buyer)->get(route('commerce.purchases'))
            ->assertOk()
            ->assertSee('هنوز فایلی نخریده‌اید')
            ->assertDontSee('سفارش پرداخت‌نشده')
            ->assertDontSee('خرید بازگشتی')
            ->assertDontSee('خرید دیگری');
    }

    public function test_the_workspace_sidebar_links_here(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('commerce.purchases'))
            ->assertSee('aria-current="page"', escape: false)
            ->assertSee('یادگیری و خرید');
    }

    private function product(string $slug, string $title): Product
    {
        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => $slug,
            'title' => $title,
            'price_toman' => 100_000,
            'status' => ProductStatus::Published,
        ])->refresh();

        $this->app->make(AddProductVersion::class)->handle($product, '1.0.0', null, UploadedFile::fake()->create('file.zip', 20));
        $this->app->make(ProductVersionReview::class)->approve($product);

        return $product->refresh();
    }

    private function payFor(Product $product, User $buyer, OrderStatus $status = OrderStatus::Paid): OrderItem
    {
        $order = Order::query()->create([
            'uuid' => (string) Str::uuid7(),
            'buyer_user_id' => $buyer->id,
            'status' => $status,
            'total_toman' => $product->price_toman,
            'paid_at' => $status === OrderStatus::Paid ? now() : null,
        ]);

        return OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'vendor_user_id' => $product->vendor_user_id,
            'unit_price_toman' => $product->price_toman,
            'commission_rate_bp' => 2000,
            'commission_toman' => 20_000,
            'vendor_amount_toman' => 80_000,
        ]);
    }
}
