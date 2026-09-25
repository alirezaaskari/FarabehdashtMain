<?php

declare(strict_types=1);

return [

    'dashboard' => [
        // شمار اعلان‌های تازه روی کارت اعلان‌های میزکار.
        'latest_notifications' => 3,
    ],

    'notifications' => [
        'per_page' => 20,
    ],

    'sms' => [
        // پیامک اعلان‌های مهم (DEC-39). پیش‌فرض خاموش است: در خط خدماتی
        // ملی‌پیامک اول باید الگوی `MELIPAYAMAK_PATTERN_NOTICE` تأیید شود.
        'enabled' => (bool) env('FBH_SMS_NOTICES', false),
        'daily_limit' => 3,
        // ساعت سکوت به وقت تهران: از ۲۲ تا ۸ صبح چیزی نمی‌رود.
        'quiet_from' => 22,
        'quiet_until' => 8,
        // پیامکی که ۱۲ ساعت از موعدش گذشته دیگر خبر نیست و نمی‌رود.
        'stale_after_hours' => 12,
    ],

    'wallet' => [
        'per_page' => 20,
    ],

    'status' => [
        // طول نوار سابقه صفحه وضعیت (docs/architecture/data-freshness-and-print.md).
        'days' => 45,
    ],

    'search' => [
        // سهم هر ماژول از صفحه جست‌وجو؛ بقیه با پیوند «همه نتایج» در خود آن بخش.
        'per_group' => 5,
    ],

];
