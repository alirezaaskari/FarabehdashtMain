<?php

declare(strict_types=1);

namespace App\Modules\Reports\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\PaymentGateway;
use App\Modules\Reports\Domain\Enums\ReportPurchaseStatus;
use App\Modules\Reports\Domain\ReportPurchase;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use RuntimeException;

final readonly class StartReportCheckout
{
    public function __construct(
        private PaymentGateway $gateway,
        private FinancialGuard $guard,
    ) {}

    public function handle(ReportPurchase $purchase, ?string $payerMobile = null): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        if ($purchase->status !== ReportPurchaseStatus::Pending) {
            throw new RuntimeException('فقط خرید در انتظار پرداخت به درگاه فرستاده می‌شود.');
        }

        try {
            $result = $this->gateway->requestPayment(new PaymentRequest(
                amount: $purchase->price(),
                description: 'صدور گزارش '.$purchase->uuid,
                callbackUrl: route('reports.purchase.callback'),
                orderUuid: $purchase->uuid,
                payerMobile: $payerMobile,
            ));
        } catch (PaymentGatewayUnavailable $exception) {
            // خریدی که هرگز به درگاه نرسید، در انتظار پرداخت نمی‌ماند.
            $purchase->forceFill(['status' => ReportPurchaseStatus::Failed])->save();

            throw $exception;
        }

        $purchase->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }
}
