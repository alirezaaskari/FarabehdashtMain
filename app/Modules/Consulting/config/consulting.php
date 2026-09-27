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

    // درخواست خدمت (بخش ۱۹-۳).
    'orders' => [
        // DEC-53: مهلت پذیرش یا رد مشاور؛ پس از آن پول کامل به کیف پول برمی‌گردد.
        'reply_hours' => 48,
        // DEC-54: پس از «انجام شد»، اگر خریدار نه تأیید کرد نه اعتراض، پول آزاد می‌شود.
        'auto_release_days' => 7,
        // نرخ کمیسیون وقتی ماژول تجارت (مرجع نرخ‌ها) خاموش است؛ همان DEC-52.
        'fallback_commission_bp' => 1500,
        'per_page' => 20,
        'need_min' => 30,
        'need_max' => 3000,
        'message_max' => 2000,
    ],

    // حدهای تعریف خدمت.
    'services' => [
        'max_per_consultant' => 10,
        'price_min_toman' => 100_000,
        'price_max_toman' => 50_000_000,
    ],

];
