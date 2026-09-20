<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Sms\SmsMessage;
use RuntimeException;

/**
 * ارسال پیامک.
 *
 * زیرساخت مشترک است، نه دارایی ماژول Identity؛ ماژول اعلان‌ها هم از همین
 * قرارداد استفاده می‌کند. تعویض سرویس‌دهنده فقط یک پیاده‌سازی تازه می‌خواهد.
 *
 * پیام یک شیء است و نه رشته، چون خط خدماتی ایران با الگو کار می‌کند و
 * درایور الگو به **مقدار متغیرها** نیاز دارد، نه به جمله رندرشده.
 * توضیح کامل در `App\Support\Sms\SmsMessage`.
 */
interface SmsSender
{
    /**
     * @throws RuntimeException اگر ارسال قطعی ناموفق باشد
     */
    public function send(string $mobile, SmsMessage $message): void;
}
