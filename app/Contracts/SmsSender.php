<?php

declare(strict_types=1);

namespace App\Contracts;

use RuntimeException;

/**
 * ارسال پیامک.
 *
 * زیرساخت مشترک است، نه دارایی ماژول Identity؛ ماژول اعلان‌ها هم از همین
 * قرارداد استفاده می‌کند. تعویض سرویس‌دهنده فقط یک پیاده‌سازی تازه می‌خواهد.
 */
interface SmsSender
{
    /**
     * @throws RuntimeException اگر ارسال قطعی ناموفق باشد
     */
    public function send(string $mobile, string $message): void;
}
