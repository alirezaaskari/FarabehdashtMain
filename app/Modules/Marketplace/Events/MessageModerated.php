<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Marketplace\Domain\MarketMessage;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر پیام نگه‌داشته را رساند یا رد کرد. رد یک اخطار است و اگر به سقف برسد
 * دسترسی فرستنده بسته می‌شود.
 */
final readonly class MessageModerated implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public MarketMessage $message,
        public int $adminId,
        public bool $approved,
        public bool $blocked,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->approved ? 'marketplace.message_approved' : 'marketplace.message_rejected',
            subjectType: MarketMessage::class,
            subjectId: $this->message->uuid,
            actorId: $this->adminId,
            after: ['status' => $this->message->status->value],
            context: ['sender_id' => $this->message->sender_user_id, 'blocked' => $this->blocked],
        );
    }

    public function userNotices(): array
    {
        if ($this->approved) {
            return [MessagePosted::notice($this->message)];
        }

        return [new UserNotice(
            recipientId: $this->message->sender_user_id,
            kind: $this->blocked ? 'marketplace.access_blocked' : 'marketplace.message_rejected',
            title: $this->blocked
                ? 'دسترسی پیشنهاد و پیام شما در بازار پروژه بسته شد'
                : 'پیام شما رد شد و به طرف مقابل نرسید',
            body: 'شماره، ایمیل، لینک و نام پیام‌رسان در بازار پروژه رد و بدل نمی‌شود؛ گفت‌وگو و پرداخت درون سایت می‌ماند تا پول در امانت محفوظ باشد.',
            routeName: 'market.bids.show',
            routeParameters: ['uuid' => $this->message->bid->uuid],
        )];
    }
}
