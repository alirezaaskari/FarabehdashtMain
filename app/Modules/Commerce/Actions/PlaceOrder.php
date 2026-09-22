<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Contracts\CommissionCalculator;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\OrderItem;
use App\Modules\Commerce\Domain\Product;
use App\Support\Money;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ساخت سفارش از فهرست شناسه محصول (سبد خرید).
 *
 * قیمت و کمیسیون همین‌جا، لحظه ساخت سفارش، Snapshot می‌شوند — نه لحظه
 * تأیید پرداخت. اگر مدیر بین ساخت سفارش و پرداخت نرخ کمیسیون را عوض کند،
 * این سفارش با نرخ لحظه ثبتش می‌ماند؛ دقیقاً همان اصل «مرجع یگانه کمیسیون».
 *
 * محصولی که دیگر منتشر نیست (فروشنده بازنشسته‌اش کرده) بی‌صدا از سفارش کنار
 * گذاشته می‌شود، نه اینکه کل سفارش رد شود — بقیه سبد هنوز معتبر است.
 */
final readonly class PlaceOrder
{
    public function __construct(
        private DatabaseManager $db,
        private CommissionCalculator $commission,
    ) {}

    /** @param  list<int>  $productIds */
    public function handle(int $buyerUserId, array $productIds): Order
    {
        $products = Product::query()->published()->whereIn('id', array_unique($productIds))->get();

        if ($products->isEmpty()) {
            throw new InvalidArgumentException('هیچ محصول قابل‌خریدی در سبد نیست.');
        }

        return $this->db->transaction(function () use ($buyerUserId, $products): Order {
            $order = Order::query()->create([
                'uuid' => (string) Str::uuid7(),
                'buyer_user_id' => $buyerUserId,
                'status' => OrderStatus::Pending,
                'total_toman' => 0,
            ]);

            $total = Money::zero();

            foreach ($products as $product) {
                $split = $this->commission->split($product->price(), 'shop');

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'vendor_user_id' => $product->vendor_user_id,
                    'unit_price_toman' => $product->price_toman,
                    'commission_rate_bp' => $split->rateBp,
                    'commission_toman' => $split->commission->toman,
                    'vendor_amount_toman' => $split->vendorAmount->toman,
                ]);

                $total = $total->plus($product->price());
            }

            $order->forceFill(['total_toman' => $total->toman])->save();

            return $order->refresh();
        });
    }
}
