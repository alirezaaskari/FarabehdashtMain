<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Audit\AuditEntry;

/**
 * نوشتن مستقیم در دفتر رویداد، برای جایی که رویداد دامنه‌ای معنی ندارد
 * (مثل ورود موفق یا مشاهده اطلاعات تماس کارجو).
 *
 * مسیر معمول همچنان انتشار یک AuditableEvent است؛ این قرارداد استثناست نه قاعده.
 */
interface AuditTrail
{
    public function record(AuditEntry $entry): void;
}
