<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Contracts\LedgerBalanceReader;
use App\Models\User;
use App\Modules\Commerce\Actions\CompleteOrderPayment;
use App\Modules\Commerce\Actions\IssueRefund;
use App\Modules\Commerce\Actions\PlaceOrder;
use App\Modules\Commerce\Actions\PreviewRefund;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\OrderItem;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Ledger\Domain\Wallet;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۱۲ — نیمه «بازگشت وجه»: کامل و جزئی، سازگار با کمیسیون.
 */
final class RefundTest extends TestCase
{
    use RefreshDatabase;

    /** ردیف سفارشی که واقعاً از جریان خرید عبور کرده — نرخ کمیسیون همیشه ۲۰٪ (پیش‌فرض تنظیمات). */
    private function paidItem(int $priceToman = 100_000): OrderItem
    {
        $vendor = User::factory()->create();
        $buyer = User::factory()->create();

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => $vendor->id,
            'slug' => 'p-'.Str::random(8),
            'title' => 'محصول آزمایشی',
            'price_toman' => $priceToman,
            'status' => ProductStatus::Published,
        ]);

        $order = $this->app->make(PlaceOrder::class)->handle($buyer->id, [$product->id]);
        $this->app->make(CompleteOrderPayment::class)->handle($order, 'REF-'.Str::random(8));

        return $order->items()->sole();
    }

    /** یک سفارش با دو ردیف از دو فروشنده جدا — هر دو واقعاً از جریان خرید عبور کرده‌اند. */
    private function twoVendorOrder(): Order
    {
        $buyer = User::factory()->create();

        $productA = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'p-'.Str::random(8),
            'title' => 'محصول اول',
            'price_toman' => 100_000,
            'status' => ProductStatus::Published,
        ]);

        $productB = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'p2-'.Str::random(8),
            'title' => 'محصول دوم',
            'price_toman' => 40_000,
            'status' => ProductStatus::Published,
        ]);

        $order = $this->app->make(PlaceOrder::class)->handle($buyer->id, [$productA->id, $productB->id]);

        return $this->app->make(CompleteOrderPayment::class)->handle($order, 'REF-'.Str::random(8));
    }

    public function test_preview_does_not_write_anything(): void
    {
        $item = $this->paidItem();

        $effect = $this->app->make(PreviewRefund::class)->handle($item, Money::toman(20_000));

        $this->assertSame(20_000, $effect->walletCredit->toman);
        $this->assertSame(4_000, $effect->platformRevenueDebit->toman);
        $this->assertSame(16_000, $effect->vendorPayableDebit->toman);

        $this->assertSame(0, LedgerTransaction::query()->where('kind', 'commerce.refund_issued')->count());
        $this->assertSame(OrderStatus::Paid, $item->order->status);
    }

    public function test_a_full_refund_marks_the_order_refunded_and_balances_the_ledger(): void
    {
        $admin = User::factory()->create();
        $item = $this->paidItem(100_000);

        $refund = $this->app->make(IssueRefund::class)->handle($item, Money::toman(100_000), $admin->id, 'انصراف کامل');

        $this->assertSame(100_000, $refund->amount_toman);
        $this->assertSame(OrderStatus::Refunded, $item->order->fresh()->status);
        $this->assertTrue($item->refresh()->isFullyRefunded());

        $transaction = LedgerTransaction::query()->where('reference_id', $refund->uuid)->sole();
        $entries = LedgerEntry::query()->where('transaction_id', $transaction->id)->get();
        $net = $entries->sum(fn (LedgerEntry $e): int => $e->amount_toman * $e->direction->sign());
        $this->assertSame(0, $net);

        $wallet = Wallet::query()->where('user_id', $item->order->buyer_user_id)->firstOrFail();
        $this->assertSame(100_000, $wallet->cached_balance_toman);

        $vendorOwed = app(LedgerBalanceReader::class)->balanceOf(LedgerAccountRef::vendorPayable($item->vendor_user_id));
        $this->assertSame(0, $vendorOwed->toman, 'بدهی فروشنده باید کامل صفر شود.');

        $revenue = app(LedgerBalanceReader::class)->balanceOf(new LedgerAccountRef(AccountType::PlatformRevenue));
        $this->assertSame(0, $revenue->toman, 'کمیسیون این سفارش باید کامل برگردد.');
    }

    public function test_two_partial_refunds_eventually_complete_the_order(): void
    {
        $admin = User::factory()->create();
        $item = $this->paidItem(100_000);

        $this->app->make(IssueRefund::class)->handle($item, Money::toman(30_000), $admin->id);
        $this->assertSame(OrderStatus::PartiallyRefunded, $item->order->fresh()->status);
        $this->assertSame(70_000, $item->refresh()->remainingRefundable()->toman);

        $this->app->make(IssueRefund::class)->handle($item, Money::toman(70_000), $admin->id);
        $this->assertSame(OrderStatus::Refunded, $item->order->fresh()->status);
        $this->assertTrue($item->refresh()->isFullyRefunded());
    }

    public function test_refunding_more_than_the_remaining_amount_is_rejected(): void
    {
        $admin = User::factory()->create();
        $item = $this->paidItem(50_000);

        $this->expectException(InvalidArgumentException::class);

        $this->app->make(IssueRefund::class)->handle($item, Money::toman(60_000), $admin->id);
    }

    public function test_an_order_with_two_items_stays_partial_until_both_are_fully_refunded(): void
    {
        $admin = User::factory()->create();
        $order = $this->twoVendorOrder();
        [$itemA, $itemB] = $order->items()->orderBy('id')->get()->all();

        $this->app->make(IssueRefund::class)->handle($itemA, $itemA->unitPrice(), $admin->id);
        $this->assertSame(OrderStatus::PartiallyRefunded, $order->fresh()->status);

        $this->app->make(IssueRefund::class)->handle($itemB, $itemB->unitPrice(), $admin->id);
        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
    }
}
