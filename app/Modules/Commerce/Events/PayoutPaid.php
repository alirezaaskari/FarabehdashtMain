<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

final readonly class PayoutPaid implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(public PayoutRequest $payout, public int $actorId) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'commerce.payout_paid',
            subjectType: PayoutRequest::class,
            subjectId: $this->payout->uuid,
            actorId: $this->actorId,
            after: ['amount_toman' => $this->payout->amount_toman, 'status' => $this->payout->status->value],
            context: ['vendor_user_id' => $this->payout->user_id, 'bank_reference' => $this->payout->bank_reference],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->payout->user_id,
            kind: 'commerce.payout_paid',
            title: sprintf('تسویه %s به حساب شما واریز شد', $this->payout->amount()->format()),
            body: $this->payout->bank_reference !== null ? 'شماره پیگیری بانک: '.$this->payout->bank_reference : null,
            routeName: 'commerce.vendor.settlement',
        )];
    }
}
