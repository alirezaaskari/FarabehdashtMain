<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * محتوایی که نسخه‌های پیشینش نگه داشته می‌شود.
 *
 * مدل خودش می‌گوید چه چیزی ارزش نسخه‌برداری دارد؛ نگه‌داشتن کل رکورد یعنی
 * ذخیره صدها بار `updated_at` و `view_count` که هیچ‌کس به تاریخچه‌شان نگاه
 * نمی‌کند.
 *
 * فقط مدل Eloquent این قرارداد را پیاده می‌کند؛ `RevisionRecorder` همین را
 * در زمان اجرا بررسی می‌کند. قرارداد عمداً `getKey()` را اعلام نمی‌کند تا با
 * امضای بدون‌نوعِ Eloquent تعارض نسازد.
 */
interface Revisable
{
    /**
     * فیلدهایی که نسخه از آن‌ها گرفته می‌شود.
     *
     * @return array<string, mixed>
     */
    public function revisionSnapshot(): array;
}
