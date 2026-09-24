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
