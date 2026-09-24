<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Modules\Commerce\Actions\CompleteOrderPayment;
use App\Modules\Commerce\Actions\PlaceOrder;
use App\Modules\Commerce\Actions\StartCheckout;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Services\Cart;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * سبد → سفارش → درگاه → Callback.
 *
 * سفارش پیش از موفقیت درگاه هیچ اثر مالی ندارد (`CompleteOrderPayment` را
 * فقط این کنترلر، فقط پس از `verify()` موفق، صدا می‌زند).
 */
final readonly class CheckoutController
{
    public function __construct(
        private Cart $cart,
        private PlaceOrder $placeOrder,
        private StartCheckout $startCheckout,
        private CompleteOrderPayment $completeOrderPayment,
        private PaymentGateway $gateway,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        abort_if($this->cart->isEmpty(), 422, 'سبد خرید خالی است.');

        $user = $request->user();
        abort_if($user === null, 403);

        $order = $this->placeOrder->handle($user, $this->cart->productIds());
        $result = $this->startCheckout->handle($order, $user->mobile ?? null);

        return redirect()->away($result->redirectUrl);
    }

    public function callback(Request $request): View
    {
        $order = Order::query()
            ->where('gateway_authority', (string) $request->query('Authority'))
            ->firstOrFail();

        if ($order->status === OrderStatus::Paid) {
            $this->cart->clear();

            return view('commerce::checkout-success', ['order' => $order]);
        }

        if ((string) $request->query('Status') !== 'OK') {
            $order->forceFill(['status' => OrderStatus::Failed])->save();

            return view('commerce::checkout-failed', ['order' => $order, 'reason' => 'پرداخت توسط شما لغو شد.']);
        }

        $verification = $this->gateway->verify((string) $order->gateway_authority, $order->total());

        if (! $verification->successful) {
            $order->forceFill(['status' => OrderStatus::Failed])->save();

            return view('commerce::checkout-failed', ['order' => $order, 'reason' => $verification->failureReason]);
        }

        $paid = $this->completeOrderPayment->handle($order, $verification->referenceId ?? '');
        $this->cart->clear();

        return view('commerce::checkout-success', ['order' => $paid]);
    }
}
