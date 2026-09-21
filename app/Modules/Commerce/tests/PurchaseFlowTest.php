<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Services\Payments\FakeZarinPalGateway;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Support\Ledger\EntryDirection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۱۲: «خرید، بازگشت کامل و جزئی، کمیسیون و تسویه با هم
 * سازگارند.» این فایل نیمه «خرید» را می‌سنجد.
 */
final class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    private FakeZarinPalGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakeZarinPalGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    private function publishedProduct(int $vendorUserId, int $priceToman = 100_000): Product
    {
        return Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => $vendorUserId,
            'slug' => 'p-'.Str::random(8),
            'title' => 'محصول آزمایشی',
            'price_toman' => $priceToman,
            'status' => ProductStatus::Published,
        ])->refresh();
    }

    private function checkout(User $buyer, Product ...$products): Order
    {
        foreach ($products as $product) {
            $this->actingAs($buyer)->post(route('commerce.cart.add', $product))->assertRedirect();
        }

        $this->actingAs($buyer)->post(route('commerce.checkout'))->assertRedirect();

        return Order::query()->forBuyer($buyer->id)->latest('id')->firstOrFail();
    }

    public function test_a_full_purchase_records_a_balanced_ledger_transaction_and_grants_access(): void
    {
        $vendor = User::factory()->create();
        $buyer = User::factory()->create();
        $product = $this->publishedProduct($vendor->id, 100_000);

        $order = $this->checkout($buyer, $product);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertNotNull($order->gateway_authority);

        $this->get(route('commerce.callback', [
            'Authority' => $order->gateway_authority,
            'Status' => 'OK',
        ]))->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->gateway_ref_id);

        $transaction = LedgerTransaction::query()->where('reference_id', $order->uuid)->sole();
        $entries = LedgerEntry::query()->where('transaction_id', $transaction->id)->get();

        $net = $entries->sum(fn (LedgerEntry $entry): int => $entry->amount_toman * $entry->direction->sign());
        $this->assertSame(0, $net, 'جمع بستانکار و بدهکار تراکنش باید صفر شود.');

        $credits = $entries->where('direction', EntryDirection::Credit)->sum('amount_toman');
        $this->assertSame(100_000, $credits);
    }

    public function test_two_items_from_different_vendors_credit_separate_vendor_payables(): void
    {
        $vendorA = User::factory()->create();
        $vendorB = User::factory()->create();
        $buyer = User::factory()->create();

        $productA = $this->publishedProduct($vendorA->id, 100_000);
        $productB = $this->publishedProduct($vendorB->id, 50_000);

        $order = $this->checkout($buyer, $productA, $productB);

        $this->get(route('commerce.callback', [
            'Authority' => $order->gateway_authority,
            'Status' => 'OK',
        ]))->assertOk();

        $this->assertSame(150_000, $order->refresh()->total_toman);

        $transaction = LedgerTransaction::query()->where('reference_id', $order->uuid)->sole();
        $entries = LedgerEntry::query()->where('transaction_id', $transaction->id)->with('account')->get();

        // یک بدهکار خزانه + یک بستانکار درآمد پلتفرم + دو بستانکار جدا برای دو فروشنده.
        $this->assertCount(4, $entries);

        $net = $entries->sum(fn (LedgerEntry $entry): int => $entry->amount_toman * $entry->direction->sign());
        $this->assertSame(0, $net);
    }

    public function test_a_cancelled_payment_leaves_the_order_failed_with_no_ledger_effect(): void
    {
        $vendor = User::factory()->create();
        $buyer = User::factory()->create();
        $product = $this->publishedProduct($vendor->id);

        $order = $this->checkout($buyer, $product);

        $this->get(route('commerce.callback', [
            'Authority' => $order->gateway_authority,
            'Status' => 'NOK',
        ]))->assertOk();

        $this->assertSame(OrderStatus::Failed, $order->refresh()->status);
        $this->assertSame(0, LedgerTransaction::query()->where('reference_id', $order->uuid)->count());
    }

    public function test_a_gateway_verification_failure_leaves_the_order_failed(): void
    {
        $vendor = User::factory()->create();
        $buyer = User::factory()->create();
        $product = $this->publishedProduct($vendor->id);

        $order = $this->checkout($buyer, $product);

        $this->gateway->failNextVerification();

        $this->get(route('commerce.callback', [
            'Authority' => $order->gateway_authority,
            'Status' => 'OK',
        ]))->assertOk();

        $this->assertSame(OrderStatus::Failed, $order->refresh()->status);
    }

    public function test_replaying_the_callback_does_not_record_the_ledger_twice(): void
    {
        $vendor = User::factory()->create();
        $buyer = User::factory()->create();
        $product = $this->publishedProduct($vendor->id);

        $order = $this->checkout($buyer, $product);

        $params = ['Authority' => $order->gateway_authority, 'Status' => 'OK'];
        $this->get(route('commerce.callback', $params))->assertOk();
        $this->get(route('commerce.callback', $params))->assertOk();

        $this->assertSame(1, LedgerTransaction::query()->where('reference_id', $order->uuid)->count());
    }

    public function test_a_guest_cannot_checkout(): void
    {
        $this->post(route('commerce.checkout'))->assertRedirect(route('login'));
    }

    public function test_checking_out_an_empty_cart_is_rejected(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->post(route('commerce.checkout'))->assertStatus(422);
    }
}
