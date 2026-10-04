<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Marketplace\Domain\Enums\MessageStatus;
use App\Modules\Marketplace\Domain\MarketMessage;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * پیامی در گفت‌وگوی پیشنهاد فرستاده شد. پیام رسیده به طرف مقابل اعلان
 * می‌شود؛ پیام نگه‌داشته فقط در دفتر رویداد ثبت می‌شود، بی متن.
 */
final readonly class MessagePosted implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(public MarketMessage $message) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'marketplace.message_posted',
            subjectType: MarketMessage::class,
            subjectId: $this->message->uuid,
            actorId: $this->message->sender_user_id,
            after: ['status' => $this->message->status->value],
            context: ['bid_id' => $this->message->bid_id, 'flags' => $this->message->flags ?? []],
        );
    }

    public function userNotices(): array
    {
        if ($this->message->status !== MessageStatus::Delivered) {
            return [];
        }

        return [self::notice($this->message)];
    }

    public static function notice(MarketMessage $message): UserNotice
    {
        $bid = $message->bid;

        return new UserNotice(
            recipientId: $bid->counterpartOf($message->sender_user_id),
            kind: 'marketplace.message',
            title: 'پیام تازه درباره «'.$bid->project->title.'»',
            routeName: 'market.bids.show',
            routeParameters: ['uuid' => $bid->uuid],
        );
    }
}
