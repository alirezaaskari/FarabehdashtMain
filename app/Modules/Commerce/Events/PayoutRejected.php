<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

final readonly class PayoutRejected implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(public PayoutRequest $payout, public int $actorId) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'commerce.payout_rejected',
            subjectType: PayoutRequest::class,
            subjectId: $this->payout->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->payout->status->value],
            context: ['vendor_user_id' => $this->payout->user_id, 'note' => $this->payout->note],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->payout->user_id,
            kind: 'commerce.payout_rejected',
            title: sprintf('درخواست تسویه %s انجام نشد', $this->payout->amount()->format()),
            body: $this->payout->note,
            routeName: 'commerce.vendor.settlement',
        )];
    }
}
