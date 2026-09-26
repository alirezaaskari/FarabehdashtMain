<?php

declare(strict_types=1);

namespace App\Modules\Courses\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Courses\Domain\Enrollment;
use App\Support\Audit\AuditEntry;

/**
 * خبر واریز به دانشجو را خود دفتر کل می‌دهد (`ledger.wallet_credited`، با
 * شرح «بازگشت وجه دوره»)؛ این رویداد فقط برای دفتر رویداد است.
 */
final readonly class EnrollmentRefunded implements AuditableEvent
{
    public function __construct(
        public Enrollment $enrollment,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'courses.enrollment_refunded',
            subjectType: Enrollment::class,
            subjectId: $this->enrollment->uuid,
            actorId: $this->actorId,
            before: ['status' => 'paid'],
            after: ['status' => $this->enrollment->status->value],
            context: [
                'course_id' => $this->enrollment->course_id,
                'student_user_id' => $this->enrollment->student_user_id,
                'amount_toman' => $this->enrollment->price_toman,
                'reason' => $this->enrollment->refund_reason,
            ],
        );
    }
}
