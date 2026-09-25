<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Enrollment;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;

/**
 * پرداخت ثبت‌نام دوره از کیف پول دانشجو (DEC-37)، بدون رفتن به درگاه.
 */
final readonly class PayEnrollmentFromWallet
{
    public function __construct(
        private WalletCheckout $wallet,
        private CompleteEnrollmentPayment $complete,
    ) {}

    public function handle(Enrollment $enrollment): Enrollment
    {
        $this->wallet->assertCanPay($enrollment->student_user_id, $enrollment->price());

        return $this->complete->handle($enrollment, null, PaymentSource::Wallet);
    }
}
