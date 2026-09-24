<?php

declare(strict_types=1);

namespace App\Modules\Reports\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Reports\Domain\Report;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * گزارشی صادر شد.
 *
 * دفتر رویداد شناسه رهگیری، هش فایل و اینکه آیا هشدار کالیبراسیون نادیده
 * گرفته شد را می‌نویسد — نه عنوان، نه نام کارفرما، نه مقدار اندازه‌گیری.
 */
final readonly class ReportIssued implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public Report $report,
        public bool $calibrationAcknowledged,
        public ?string $supersedesCode,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'reports.issued',
            subjectType: Report::class,
            subjectId: $this->report->uuid,
            actorId: $this->report->user_id,
            after: [
                'tracking_code' => $this->report->tracking_code,
                'pdf_sha256' => $this->report->pdf_sha256,
                'revision' => $this->report->revision,
            ],
            context: array_filter([
                'calibration_acknowledged' => $this->calibrationAcknowledged,
                'supersedes' => $this->supersedesCode,
            ], static fn (mixed $value): bool => $value !== null && $value !== false),
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->report->user_id,
            kind: 'reports.issued',
            title: 'گزارش شما با شناسه '.$this->report->tracking_code.' صادر شد',
            body: 'فایل PDF آماده دانلود است و گیرنده می‌تواند اصالتش را با همین شناسه بررسی کند.',
            routeName: 'reports.show',
            routeParameters: ['uuid' => $this->report->uuid],
        )];
    }
}
