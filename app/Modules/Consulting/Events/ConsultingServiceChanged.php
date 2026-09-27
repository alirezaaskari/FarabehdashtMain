<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Consulting\Domain\ConsultingService;
use App\Modules\Consulting\Domain\Enums\ServiceStatus;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * خدمت فرستاده، منتشر، برگشت یا از فروش خارج شد. قیمت در دفتر رویداد می‌آید؛
 * تغییر قیمت داده حساس است.
 */
final readonly class ConsultingServiceChanged implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public ConsultingService $service,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'consulting.service_'.$this->service->status->value,
            subjectType: ConsultingService::class,
            subjectId: $this->service->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->service->status->value, 'price_toman' => $this->service->price_toman],
        );
    }

    public function userNotices(): array
    {
        [$title, $body] = match ($this->service->status) {
            ServiceStatus::Published => ['خدمت شما منتشر شد', '«'.$this->service->title.'» روی صفحه شما قابل خرید است.'],
            ServiceStatus::Rejected => ['خدمت برای اصلاح برگشت', $this->service->review_note],
            default => [null, null],
        };

        return $title === null ? [] : [new UserNotice(
            recipientId: $this->service->profile->user_id,
            kind: 'consulting.service_'.$this->service->status->value,
            title: $title,
            body: $body,
            routeName: 'consulting.services.index',
        )];
    }
}
