<?php

declare(strict_types=1);

namespace App\Support\Seo;

/** نوع Open Graph صفحه؛ شبکه‌های اجتماعی پیش‌نمایش مقاله را جدا می‌چینند. */
enum OgType: string
{
    case Website = 'website';
    case Article = 'article';
}
