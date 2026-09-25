<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * متن یا عنوانی که در پیوندهای داخلی نقش دارد عوض شد.
 *
 * ماژول پیوند روی همین قرارداد گوش می‌دهد و پیوندها را از نو می‌سازد
 * (DEC-31)، بی‌آنکه رویداد ماژول‌های محتوا را بشناسد.
 */
interface LinkableContentChanged
{
    /**
     * آیا تغییر به صفحه‌های عمومی می‌رسد. ویرایش پیش‌نویس نه مبدأ پیوند است و
     * نه مقصد، پس بازسازی نمی‌خواهد.
     */
    public function affectsPublicLinks(): bool;
}
