<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Audit\AuditEntry;

/**
 * رویدادی که باید در دفتر رویداد ثبت شود.
 *
 * هر ماژول رویداد خودش را تعریف می‌کند و فقط همین قرارداد را پیاده می‌کند؛
 * نوشتن ردیف کار ماژول Core است و ماژول منتشرکننده از آن خبر ندارد. اگر Core
 * غیرفعال باشد، رویداد منتشر می‌شود و کسی به آن گوش نمی‌دهد — بدون خطا.
 */
interface AuditableEvent
{
    public function auditEntry(): AuditEntry;
}
