<?php

declare(strict_types=1);

namespace App\Modules\Reports\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Reports\Domain\Report;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * گزارشی باطل شد — به دست مالکش، یا مدیر در صورت سوءاستفاده.
 *
 * مالک فقط وقتی اعلان می‌گیرد که کس دیگری باطلش کرده باشد؛ اعلان کاری که
 * خودش همین حالا انجام داده، فقط صندوق اعلان را شلوغ می‌کند.
 */
final readonly class ReportRevoked implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public Report $report,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'reports.revoked',
            subjectType: Report::class,
            subjectId: $this->report->uuid,
            actorId: $this->actorId,
            after: ['tracking_code' => $this->report->tracking_code],
            context: ['reason' => $this->report->revoke_reason],
        );
    }

    public function userNotices(): array
    {
        if ($this->actorId === $this->report->user_id) {
            return [];
        }

        return [new UserNotice(
            recipientId: $this->report->user_id,
            kind: 'reports.revoked',
            title: 'گزارش '.$this->report->tracking_code.' به دست مدیر سایت باطل شد',
            body: $this->report->revoke_reason,
            routeName: 'reports.show',
            routeParameters: ['uuid' => $this->report->uuid],
        )];
    }
}
