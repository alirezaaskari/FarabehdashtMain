<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * رویدادی که پس از آن یک نسخه از محتوا نگه داشته می‌شود.
 *
 * همان الگوی {@see AuditableEvent}: ماژول محتوا فقط رویداد منتشر می‌کند و
 * Core روی خود این قرارداد گوش می‌دهد، پس هیچ ماژولی سرویس نسخه Core را
 * import نمی‌کند.
 */
interface RevisionEvent
{
    public function revisable(): Revisable;

    /** چرا این نسخه گرفته شد — «انتشار»، «ویرایش از پنل». */
    public function revisionReason(): string;

    public function revisionAuthorId(): ?int;
}
