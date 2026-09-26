<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Revisions\RevisionRecord;

/**
 * خواندن نسخه‌های پیشین یک محتوای `Revisable`.
 *
 * نوشتن با رویداد `RevisionEvent` است؛ خواندن از این قرارداد تا ماژول صاحب
 * محتوا صفحه تاریخچه خودش را بی‌وابستگی به مدل‌های Core بسازد (قاعده ۱).
 */
interface RevisionHistory
{
    /**
     * نسخه‌ها از تازه به کهنه.
     *
     * @param  class-string  $type
     * @return list<RevisionRecord>
     */
    public function of(string $type, int|string $id): array;
}
