<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Models\User;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/** مدیر اخطارهای یک کاربر را پاک کرد و دسترسی بازارش دوباره باز شد. */
final readonly class MarketAccessReopened implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public int $userId,
        public int $adminId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'marketplace.access_reopened',
            subjectType: User::class,
            subjectId: (string) $this->userId,
            actorId: $this->adminId,
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->userId,
            kind: 'marketplace.access_reopened',
            title: 'دسترسی شما در بازار پروژه دوباره باز شد',
            body: 'لطفاً راه تماس رد و بدل نکنید؛ تکرارش دوباره دسترسی را می‌بندد.',
            routeName: 'market.index',
        )];
    }
}
