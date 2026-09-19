<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Audit\AuditFilter;
use App\Support\Audit\AuditRecord;

/**
 * خواندن دفتر رویداد.
 *
 * جدا از `AuditTrail` (که فقط می‌نویسد) است، چون نوشتن و خواندن دو مخاطب
 * متفاوت دارند: هر ماژولی می‌نویسد، ولی خواندن کار پنل مدیریت است.
 */
interface AuditTrailReader
{
    /** @return list<AuditRecord> */
    public function search(AuditFilter $filter, int $limit = 50, int $offset = 0): array;

    public function count(AuditFilter $filter): int;

    /**
     * فهرست کارهایی که تا حالا ثبت شده‌اند، برای ساختن فیلتر.
     *
     * @return list<string>
     */
    public function knownActions(): array;
}
