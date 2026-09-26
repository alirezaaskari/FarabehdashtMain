<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Modules\Courses\Events\EnrollmentRefunded;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Payments\PaymentSource;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * بازگشت کامل وجه یک ثبت‌نام دوره (بخش ۱۸-۱۱).
 *
 * همان قاعده بازگشت وجه فروشگاه: پول به **کیف پول دانشجو** برمی‌گردد نه به
 * کارت، و خزانه در تراکنش نیست؛ سهم مدرس و کمیسیون با همان نرخ Snapshot
 * ثبت‌نام برگردانده می‌شوند. بازگشت همیشه کامل است: دوره بخش‌پذیر نیست و
 * دسترسی با آن بسته می‌شود. ثبت‌نام رایگان یا از بسته پولی نگرفته که برگردد.
 */
final readonly class RefundEnrollment
{
    public function __construct(
        private LedgerRecorder $ledger,
        private Dispatcher $events,
    ) {}

    public function handle(Enrollment $enrollment, int $actorId, ?string $reason = null): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $actorId, $reason): Enrollment {
            $enrollment = Enrollment::query()->lockForUpdate()->findOrFail($enrollment->id);

            if ($enrollment->status !== EnrollmentStatus::Paid) {
                throw new InvalidArgumentException('فقط ثبت‌نام پرداخت‌شده وجه برگشتی دارد.');
            }

            if (! in_array($enrollment->payment_source, [PaymentSource::Gateway, PaymentSource::Wallet], true)
                || $enrollment->price()->isZero()) {
                throw new InvalidArgumentException('این ثبت‌نام رایگان یا از بسته بوده و پولی برای برگشت ندارد.');
            }

            $this->ledger->record(new LedgerTransactionRequest(
                kind: 'courses.enrollment_refunded',
                idempotencyKey: 'courses.enrollment_refunded:'.$enrollment->uuid,
                entries: $this->entries($enrollment),
                referenceType: Enrollment::class,
                referenceId: $enrollment->uuid,
                memo: $reason,
                createdBy: $actorId,
            ));

            $enrollment->forceFill([
                'status' => EnrollmentStatus::Refunded,
                'refunded_at' => now(),
                'refunded_by' => $actorId,
                'refund_reason' => $reason,
            ])->save();

            $this->events->dispatch(new EnrollmentRefunded($enrollment->load('course'), $actorId));

            return $enrollment;
        });
    }

    /** @return list<LedgerEntryLine> */
    private function entries(Enrollment $enrollment): array
    {
        $entries = [
            new LedgerEntryLine(LedgerAccountRef::wallet($enrollment->student_user_id), EntryDirection::Credit, $enrollment->price()),
        ];

        if (! $enrollment->instructorAmount()->isZero()) {
            $entries[] = new LedgerEntryLine(
                LedgerAccountRef::vendorPayable($enrollment->course->instructor_user_id),
                EntryDirection::Debit,
                $enrollment->instructorAmount(),
            );
        }

        if (! $enrollment->commission()->isZero()) {
            $entries[] = new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Debit, $enrollment->commission());
        }

        return $entries;
    }
}
