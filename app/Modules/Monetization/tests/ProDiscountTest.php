<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Commerce\Actions\PlaceOrder;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Services\Payments\FakeZarinPalGateway;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Enums\ShutdownPolicy;
use App\Modules\Monetization\Domain\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * تخفیف پایدار مشترک روی فروشگاه.
 *
 * قاعده‌ای که این فایل قفل می‌کند: تخفیف از سهم پلتفرم کم می‌شود، نه از سهم
 * فروشنده. فروشنده نباید بابت مشترک‌بودن خریدار کمتر بگیرد.
 */
final class ProDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeZarinPalGateway);
    }

    public function test_a_free_buyer_pays_the_list_price(): void
    {
        $item = $this->order(User::factory()->create());

        $this->assertSame(100_000, $item['price']);
        $this->assertSame(0, $item['discount']);
        $this->assertSame(20_000, $item['commission']);
        $this->assertSame(80_000, $item['vendor']);
    }

    public function test_a_subscriber_pays_less_and_the_discount_comes_out_of_the_commission(): void
    {
        $buyer = User::factory()->create();
        $this->subscribe($buyer);

        $item = $this->order($buyer);

        $this->assertSame(90_000, $item['price']);
        $this->assertSame(10_000, $item['discount']);
        $this->assertSame(10_000, $item['commission']);

        // همان چیزی که بدون تخفیف هم می‌گرفت.
        $this->assertSame(80_000, $item['vendor']);

        // ناوردای ردیف: پرداختی = کمیسیون + سهم فروشنده.
        $this->assertSame($item['price'], $item['commission'] + $item['vendor']);
    }

    /** خاموش‌شدن کلید اشتراک، تخفیف را حذف می‌کند — سطر جدول سند کلیدها. */
    public function test_turning_the_subscription_off_removes_the_discount(): void
    {
        $buyer = User::factory()->create();
        $this->subscribe($buyer);

        $this->app->make(ToggleRevenueStream::class)->handle(
            RevenueStream::ProSubscription,
            false,
            ShutdownPolicy::RunToEnd,
        );

        $item = $this->order($buyer);

        $this->assertSame(100_000, $item['price']);
        $this->assertSame(0, $item['discount']);
    }

    /** @return array{price: int, discount: int, commission: int, vendor: int} */
    private function order(User $buyer): array
    {
        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->getKey(),
            'slug' => 'p-'.Str::random(8),
            'title' => 'محصول آزمایشی',
            'price_toman' => 100_000,
            'status' => ProductStatus::Published,
        ]);

        $order = $this->app->make(PlaceOrder::class)->handle($buyer, [(int) $product->getKey()]);
        $item = $order->items()->firstOrFail();

        return [
            'price' => $item->unit_price_toman,
            'discount' => $item->discount_toman,
            'commission' => $item->commission_toman,
            'vendor' => $item->vendor_amount_toman,
        ];
    }

    private function subscribe(User $user): void
    {
        Subscription::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $user->getKey(),
            'started_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addMonth(),
        ]);
    }
}
