<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Modules\Commerce\Actions\CompleteOrderPayment;
use App\Modules\Commerce\Actions\PayOrderFromWallet;
use App\Modules\Commerce\Actions\PlaceOrder;
use App\Modules\Commerce\Actions\StartCheckout;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Services\Cart;
use App\Support\Payments\InsufficientWalletBalance;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentSource;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * سبد → سفارش → درگاه → Callback.
 *
 * سفارش پیش از موفقیت درگاه هیچ اثر مالی ندارد (`CompleteOrderPayment` را
 * فقط این کنترلر، فقط پس از `verify()` موفق، صدا می‌زند). پرداخت از کیف
 * پول (DEC-37) درگاه را دور می‌زند و همان اثر مالی را با بدهکارشدن کیف پول
 * خریدار ثبت می‌کند.
 */
final readonly class CheckoutController
{
    public function __construct(
        private Cart $cart,
        private PlaceOrder $placeOrder,
        private StartCheckout $startCheckout,
        private CompleteOrderPayment $completeOrderPayment,
        private PayOrderFromWallet $payFromWallet,
        private PaymentGateway $gateway,
    ) {}

    public function store(Request $request): RedirectResponse|View
    {
        abort_if($this->cart->isEmpty(), 422, 'سبد خرید خالی است.');

        $user = $request->user();
        abort_if($user === null, 403);

        $source = PaymentSource::requested($request);

        $order = $this->placeOrder->handle($user, $this->cart->productIds());

        if ($source === PaymentSource::Wallet) {
            try {
                $paid = $this->payFromWallet->handle($order);
            } catch (InsufficientWalletBalance $exception) {
                $order->forceFill(['status' => OrderStatus::Failed])->save();

                return back()->withErrors(['payment' => $exception->getMessage()]);
            }

            $this->cart->clear();

            return view('commerce::checkout-success', ['order' => $paid]);
        }

        try {
            $result = $this->startCheckout->handle($order, $user->mobile ?? null);
        } catch (PaymentGatewayUnavailable $exception) {
            report($exception);

            return view('commerce::checkout-failed', ['reason' => PaymentGatewayUnavailable::USER_MESSAGE]);
        }

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
