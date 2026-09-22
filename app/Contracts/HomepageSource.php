<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Home\HomeSection;

/**
 * ماژولی که یک بخش روی صفحه اصلی دارد.
 *
 * هر ماژول محتوایی این قرارداد را پیاده می‌کند و با برچسب `home.sources` ثبت
 * می‌شود؛ Core صفحه اصلی را می‌چیند بدون اینکه بداند چه ماژول‌هایی هستند —
 * همان الگوی `SitemapSource`.
 *
 * بازگرداندن `null` یعنی «این ماژول امروز چیزی برای نشان‌دادن ندارد»: بخش
 * حذف می‌شود، نه اینکه با قاب خالی روی صفحه بماند.
 */
interface HomepageSource
{
    public function homeSection(): ?HomeSection;
}
