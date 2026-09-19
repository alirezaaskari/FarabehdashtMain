<?php

declare(strict_types=1);

namespace App\Modules\Core\Listeners;

use App\Contracts\AuditableEvent;
use App\Contracts\AuditTrail;

/**
 * پل میان رویدادهای ماژول‌ها و دفتر رویداد.
 *
 * روی خود قرارداد AuditableEvent ثبت می‌شود، نه روی تک‌تک رویدادها؛ پس ماژول
 * تازه برای ثبت‌شدن در دفتر، هیچ سیم‌کشی اضافه‌ای لازم ندارد — فقط قرارداد را
 * پیاده می‌کند.
 */
final readonly class RecordAuditableEvent
{
    public function __construct(private AuditTrail $trail) {}

    public function handle(AuditableEvent $event): void
    {
        $this->trail->record($event->auditEntry());
    }
}
