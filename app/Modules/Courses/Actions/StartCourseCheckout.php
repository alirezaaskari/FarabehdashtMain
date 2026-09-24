<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\PaymentGateway;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use RuntimeException;

final readonly class StartCourseCheckout
{
    public function __construct(
        private PaymentGateway $gateway,
        private FinancialGuard $guard,
    ) {}

    public function handle(Enrollment $enrollment, ?string $payerMobile = null): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        if ($enrollment->status !== EnrollmentStatus::Pending) {
            throw new RuntimeException('فقط ثبت‌نام در انتظار پرداخت به درگاه فرستاده می‌شود.');
        }

        $result = $this->gateway->requestPayment(new PaymentRequest(
            amount: $enrollment->price(),
            description: 'ثبت‌نام دوره '.$enrollment->uuid,
            callbackUrl: route('courses.callback'),
            orderUuid: $enrollment->uuid,
            payerMobile: $payerMobile,
        ));

        $enrollment->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }
}
