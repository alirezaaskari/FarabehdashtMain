<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Search\SearchGroup;
use App\Support\Search\SearchQuery;

/**
 * ماژولی که محتوایش در جست‌وجوی داخلی پیدا می‌شود.
 *
 * هر ماژول جدول خودش را می‌گردد و فقط محتوای منتشرشده برمی‌گرداند؛ صفحه
 * جست‌وجو مدل هیچ ماژولی را نمی‌شناسد (قاعده ۱). برچسب روی قرارداد است تا
 * حذف ماژول جست‌وجو، ماژول‌های ثبت‌کننده را نشکند.
 */
interface SearchSource
{
    public const TAG = 'search.sources';

    /** گروه نتایج، یا null وقتی چیزی پیدا نشد. */
    public function search(SearchQuery $query, int $limit): ?SearchGroup;

    /**
     * مقصد مستقیم وقتی عبارت یک شناسه یکتای بی‌ابهام است (مثل شماره CAS).
     *
     * کاربری که «108-88-3» را جست‌وجو می‌کند فهرست نمی‌خواهد؛ صفحه ماده را
     * می‌خواهد.
     */
    public function directUrl(SearchQuery $query): ?string;
}
