<?php

declare(strict_types=1);

namespace App\Modules\Reports\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Reports\Domain\ReportPurchase;
use App\Support\Audit\AuditEntry;

/**
 * صدور یک گزارش خریده شد. دفتر رویداد شناسه خرید، مبلغ و منبع پرداخت را
 * می‌نویسد؛ عنوان گزارش و نام کارفرما را نه.
 */
final readonly class ReportPurchased implements AuditableEvent
{
    public function __construct(public ReportPurchase $purchase) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'reports.purchased',
            subjectType: ReportPurchase::class,
            subjectId: $this->purchase->uuid,
            actorId: $this->purchase->user_id,
            after: [
                'price_toman' => $this->purchase->price_toman,
                'payment_source' => $this->purchase->payment_source->value,
            ],
        );
    }
}
