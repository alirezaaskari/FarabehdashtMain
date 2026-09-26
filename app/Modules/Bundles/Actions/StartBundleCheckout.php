<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\PaymentGateway;
use App\Modules\Bundles\Domain\BundlePurchase;
use App\Modules\Bundles\Domain\Enums\PurchaseStatus;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use RuntimeException;

final readonly class StartBundleCheckout
{
    public function __construct(
        private PaymentGateway $gateway,
        private FinancialGuard $guard,
    ) {}

    public function handle(BundlePurchase $purchase, ?string $payerMobile = null): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        if ($purchase->status !== PurchaseStatus::Pending) {
            throw new RuntimeException('فقط خرید در انتظار پرداخت به درگاه فرستاده می‌شود.');
        }

        try {
            $result = $this->gateway->requestPayment(new PaymentRequest(
                amount: $purchase->price(),
                description: 'بسته راه‌حل '.$purchase->uuid,
                callbackUrl: route('bundles.purchase.callback'),
                orderUuid: $purchase->uuid,
                payerMobile: $payerMobile,
            ));
        } catch (PaymentGatewayUnavailable $exception) {
            $purchase->forceFill(['status' => PurchaseStatus::Failed])->save();

            throw $exception;
        }

        $purchase->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }
}
