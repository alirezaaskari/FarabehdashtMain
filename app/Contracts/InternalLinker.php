<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Linking\LinkRef;
use App\Support\Linking\LinkSegment;

/**
 * موتور پیوند داخلی، از نگاه ماژولی که متن نمایش می‌دهد.
 *
 * اگر ماژول پیوند خاموش باشد، کسی این قرارداد را نمی‌بندد و مصرف‌کننده
 * متن را بدون پیوند نشان می‌دهد — بدون خطا.
 */
interface InternalLinker
{
    /**
     * بندهای یک سند، هر بند به تکه‌های متن و پیوند.
     *
     * فقط پیوندهایی که در بازسازی آخر برای این سند ثبت شده‌اند گذاشته
     * می‌شوند، هر مقصد یک بار و در اولین جایی که آمده.
     *
     * @param  list<string>  $paragraphs
     * @return list<list<LinkSegment>>
     */
    public function link(string $documentKey, array $paragraphs): array;

    /**
     * سندهایی که به این مقصد پیوند داده‌اند («مقاله‌هایی که به این اشاره دارند»).
     *
     * @return list<LinkRef>
     */
    public function mentionedIn(string $targetKey, int $limit): array;

    /**
     * مقصدهایی که این سند به آن‌ها پیوند داده، به ترتیب آمدن در متن.
     *
     * @return list<LinkRef>
     */
    public function mentions(string $documentKey): array;
}
