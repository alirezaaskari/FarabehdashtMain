<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Jobs\Domain\BankRequest;
use App\Modules\Jobs\Domain\Enums\BankRequestStatus;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * درخواست تماس پذیرفته، رد یا بی‌پاسخ ماند. کارفرما خبر می‌گیرد؛ در دو حالت
 * آخر اعتبارش برگشته است (DEC-72).
 */
final readonly class BankRequestAnswered implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public BankRequest $request,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.bank_request_'.$this->request->status->value,
            subjectType: BankRequest::class,
            subjectId: $this->request->uuid,
            actorId: $this->actorId,
            before: ['status' => BankRequestStatus::Pending->value],
            after: ['status' => $this->request->status->value],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->request->employer_id,
            kind: 'jobs.bank_answered',
            title: match ($this->request->status) {
                BankRequestStatus::Accepted => 'یک کارجوی بانک رزومه درخواست تماس شما را پذیرفت',
                BankRequestStatus::Declined => 'یک کارجوی بانک رزومه درخواست تماس را نپذیرفت',
                default => 'یک درخواست تماس بانک رزومه بی‌پاسخ ماند',
            },
            body: match (true) {
                $this->request->status === BankRequestStatus::Accepted => 'نام و راه تماس در فهرست درخواست‌ها آمده است.',
                $this->request->charged => 'اعتبار این درخواست به بسته شما برگشت.',
                default => null,
            },
            routeName: 'jobs.talent.requests',
        )];
    }
}
