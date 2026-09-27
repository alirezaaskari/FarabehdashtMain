<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Reporting\ReviewableReport;

/**
 * گزارش صادرشده برای بررسی متخصص (بخش ۱۹-۴).
 *
 * فقط سند منجمد همان گزارش بیرون می‌آید، نه پروژه یا داده‌های دیگر کاربر.
 * بررسی‌کننده نسخه فقط‌خواندنی را می‌بیند و گزارش هیچ نشانی از بررسی نمی‌گیرد.
 */
interface ReviewableReports
{
    /**
     * گزارش‌های معتبر (صادرشده، باطل‌نشده، جایگزین‌نشده) این کاربر، تازه‌ترین اول.
     *
     * @return list<ReviewableReport>
     */
    public function issuedBy(int $userId, int $limit = 20): array;

    /** یک گزارش معتبر همین کاربر، یا تهی. */
    public function ownedBy(int $userId, string $uuid): ?ReviewableReport;

    /**
     * سند منجمد برای نمایش به طرفین بررسی، هر وضعیتی که امروز داشته باشد.
     * دسترسی را فراخوان می‌سنجد (طرف درخواست بودن).
     */
    public function find(string $uuid): ?ReviewableReport;
}
