<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Site\Shelves;
use Illuminate\Database\Query\Builder;

/**
 * ماژولی که فهرست عمومی‌اش ممکن است هنوز خالی باشد (کاریابی، دوره‌ها، فروشگاه…).
 *
 * پوسته سایت و صفحه اصلی پیوند ورود به فهرست خالی را نشان نمی‌دهند: تازه‌واردی
 * که از منو به «هنوز چیزی منتشر نشده» می‌رسد، سایت را نیمه‌کاره می‌بیند. خود
 * صفحه فهرست سر جایش می‌ماند و راه‌های پرکردنش (آگهی بده، مدرس شو) هم.
 *
 * ماژول با برچسب {@see self::TAG} ثبت می‌شود؛ همه پرس‌وجوها در یک پرس‌وجوی
 * `exists` اجرا می‌شوند ({@see Shelves}).
 */
interface ShelfSource
{
    public const TAG = 'site.shelves';

    /**
     * نام مسیر فهرست عمومی => پرس‌وجوی همان چیزی که آن فهرست نشان می‌دهد.
     *
     * @return array<string, Builder>
     */
    public function shelves(): array;
}
