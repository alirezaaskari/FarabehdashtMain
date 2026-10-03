<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/** مدیر درباره پروژه تصمیم گرفت؛ تأیید یعنی انتشار. */
final readonly class ProjectReviewed implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public MarketProject $project,
        public int $adminId,
        public bool $approved,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->approved ? 'marketplace.project_approved' : 'marketplace.project_rejected',
            subjectType: MarketProject::class,
            subjectId: $this->project->uuid,
            actorId: $this->adminId,
            after: ['status' => $this->project->status->value],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->project->client_user_id,
            kind: $this->approved ? 'marketplace.project_approved' : 'marketplace.project_rejected',
            title: $this->approved
                ? 'پروژه «'.$this->project->title.'» منتشر شد و پیشنهاد می‌پذیرد'
                : 'پروژه «'.$this->project->title.'» برای اصلاح برگشت',
            body: $this->approved ? null : (string) $this->project->review_note,
            routeName: 'market.client.index',
        )];
    }
}
