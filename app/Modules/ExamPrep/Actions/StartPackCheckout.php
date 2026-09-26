<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\PaymentGateway;
use App\Modules\ExamPrep\Domain\Enums\PurchaseStatus;
use App\Modules\ExamPrep\Domain\PackPurchase;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use RuntimeException;

final readonly class StartPackCheckout
{
    public function __construct(
        private PaymentGateway $gateway,
        private FinancialGuard $guard,
    ) {}

    public function handle(PackPurchase $purchase, ?string $payerMobile = null): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        if ($purchase->status !== PurchaseStatus::Pending) {
            throw new RuntimeException('فقط خرید در انتظار پرداخت به درگاه فرستاده می‌شود.');
        }

        try {
            $result = $this->gateway->requestPayment(new PaymentRequest(
                amount: $purchase->price(),
                description: 'بسته آزمون '.$purchase->uuid,
                callbackUrl: route('exam_prep.purchase.callback'),
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
