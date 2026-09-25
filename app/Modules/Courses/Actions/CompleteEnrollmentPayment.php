<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Payments\PaymentSource;
use RuntimeException;

/**
 * ثبت اثر مالی یک ثبت‌نام تأییدشده در دفتر کل — همان الگوی
 * `CompleteOrderPayment` ماژول تجارت.
 *
 * بستانکار بدهی مدرس از همان `LedgerAccountRef::vendorPayable()` عبور
 * می‌کند: مدرس و فروشنده هویت مالی مشترک دارند (ADR-0002 §۵) — یک کیف پول،
 * یک تسویه، پس یک نوع حساب.
 *
 * منبع پرداخت طرف بدهکار را تعیین می‌کند: خزانه برای درگاه، کیف پول دانشجو
 * برای کیف پول (DEC-37). دوره رایگان (DEC-38) هیچ تراکنشی در دفتر کل ندارد.
 */
final readonly class CompleteEnrollmentPayment
{
    public function __construct(private LedgerRecorder $ledger) {}

    public function handle(Enrollment $enrollment, ?string $gatewayRefId, PaymentSource $source = PaymentSource::Gateway): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::Pending) {
            throw new RuntimeException('فقط ثبت‌نام در انتظار پرداخت تکمیل می‌شود.');
        }

        if (($source === PaymentSource::Free) !== $enrollment->price()->isZero()) {
            throw new RuntimeException('فقط دوره رایگان بدون پرداخت ثبت‌نام می‌شود و دوره رایگان پرداختی ندارد.');
        }

        if ($source !== PaymentSource::Free) {
            $this->recordPayment($enrollment, $source);
        }

        $enrollment->forceFill([
            'status' => EnrollmentStatus::Paid,
            'gateway_ref_id' => $gatewayRefId,
            'payment_source' => $source,
            'paid_at' => now(),
        ])->save();

        return $enrollment->refresh();
    }

    private function recordPayment(Enrollment $enrollment, PaymentSource $source): void
    {
        $entries = [
            new LedgerEntryLine($source->debitAccount($enrollment->student_user_id), EntryDirection::Debit, $enrollment->price()),
        ];

        if (! $enrollment->instructorAmount()->isZero()) {
            $entries[] = new LedgerEntryLine(
                LedgerAccountRef::vendorPayable($enrollment->course->instructor_user_id),
                EntryDirection::Credit,
                $enrollment->instructorAmount(),
            );
        }

        if (! $enrollment->commission()->isZero()) {
            $entries[] = new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $enrollment->commission());
        }

        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'courses.enrollment_paid',
            idempotencyKey: 'courses.enrollment_paid:'.$enrollment->uuid,
            entries: $entries,
            referenceType: Enrollment::class,
            referenceId: $enrollment->uuid,
            memo: 'ثبت‌نام دوره '.$enrollment->uuid,
        ));
    }
}
