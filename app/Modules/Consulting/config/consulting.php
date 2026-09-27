<?php

declare(strict_types=1);

return [

    // مشاور در هر صفحه فهرست عمومی.
    'per_page' => 24,

    // پاسخ‌های منتشرشده‌ای که صفحه هر مشاور نشان می‌دهد.
    'answers_on_profile' => 6,

    // کمینه و بیشینه طول متن‌ها (نویسه).
    'limits' => [
        'name_max' => 80,
        'headline_max' => 120,
        'bio_min' => 80,
        'bio_max' => 3000,
        'history_max' => 2000,
    ],

    // مدرک‌هایی که مشاور برای بررسی مدیر می‌فرستد؛ خصوصی روی دیسک local (DEC-50).
    'documents' => [
        'max' => 5,
        'max_kb' => 5120,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
        'directory' => 'consulting/documents',
    ],

];
