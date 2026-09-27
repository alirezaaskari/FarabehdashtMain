<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Jobs\Domain\BankRequest;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/** کارفرما از بانک رزومه درخواست تماس فرستاد؛ کارجو خبر می‌گیرد. */
final readonly class BankRequestSent implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public BankRequest $request,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.bank_request_sent',
            subjectType: BankRequest::class,
            subjectId: $this->request->uuid,
            actorId: $this->request->employer_id,
            after: ['status' => $this->request->status->value],
            context: ['company' => $this->request->company_id, 'jobseeker' => $this->request->jobseeker_id, 'charged' => $this->request->charged],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->request->jobseeker_id,
            kind: 'jobs.bank_request',
            title: '«'.$this->request->company->name.'» می‌خواهد با شما تماس بگیرد',
            body: 'تا پاسخ شما، نام و شماره‌تان را نمی‌بیند.',
            routeName: 'jobs.bank.index',
        )];
    }
}
