<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Domain\PostingPayment;
use App\Support\Audit\AuditEntry;

/**
 * یک دوره انتشار آگهی شروع شد: پرداخت تأیید شد یا دوره رایگان بود. از همین
 * رویداد، هشدار شغل (بخش ۲۰-۴) آگهی تازه را می‌شناسد.
 */
final readonly class PostingPublished implements AuditableEvent
{
    public function __construct(
        public JobPosting $posting,
        public PostingPayment $payment,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.posting_published',
            subjectType: JobPosting::class,
            subjectId: $this->posting->uuid,
            actorId: $this->actorId,
            after: ['expires_at' => $this->posting->expires_at?->toIso8601String()],
            context: [
                'payment' => $this->payment->uuid,
                'price_toman' => $this->payment->price_toman,
                'days' => $this->payment->days,
                'free' => $this->payment->is_free,
            ],
        );
    }
}
