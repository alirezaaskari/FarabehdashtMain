<?php

declare(strict_types=1);

return [

    // پرسش در هر صفحه فهرست عمومی، «پرسش‌های من» و صف پاسخ‌دهنده.
    'per_page' => 20,

    // سقف پرسش تازه هر کاربر در ساعت؛ جلوی سیل پرسش در صف مدیر را می‌گیرد.
    'ask_per_hour' => 5,

    // کمینه و بیشینه طول متن‌ها (نویسه).
    'limits' => [
        'title_min' => 10,
        'title_max' => 200,
        'body_min' => 30,
        'body_max' => 5000,
        'answer_min' => 50,
        'answer_max' => 8000,
    ],

];
