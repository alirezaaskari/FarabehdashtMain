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
use RuntimeException;

/**
 * ثبت اثر مالی یک ثبت‌نام تأییدشده در دفتر کل — همان الگوی
 * `CompleteOrderPayment` ماژول تجارت.
 *
 * بستانکار بدهی مدرس از همان `LedgerAccountRef::vendorPayable()` عبور
 * می‌کند: مدرس و فروشنده هویت مالی مشترک دارند (ADR-0002 §۵) — یک کیف پول،
 * یک تسویه، پس یک نوع حساب.
 */
final readonly class CompleteEnrollmentPayment
{
    public function __construct(private LedgerRecorder $ledger) {}

    public function handle(Enrollment $enrollment, string $gatewayRefId): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::Pending) {
            throw new RuntimeException('فقط ثبت‌نام در انتظار پرداخت تکمیل می‌شود.');
        }

        $entries = [
            new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Debit, $enrollment->price()),
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

        $enrollment->forceFill([
            'status' => EnrollmentStatus::Paid,
            'gateway_ref_id' => $gatewayRefId,
            'paid_at' => now(),
        ])->save();

        return $enrollment->refresh();
    }
}
