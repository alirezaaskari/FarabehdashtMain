<?php

declare(strict_types=1);

return [

    /*
    | تعریف پروژه (بخش ۲۱-۲). کمینه بودجه و مهلت پیشنهاد (DEC-79) تنظیم پنل
    | «قیمت‌ها و زمان‌ها»اند و این عددها فقط پیش‌فرض روز نصب.
    */
    'projects' => [
        'budget_min_toman' => 1_000_000,
        'budget_max_toman' => 10_000_000_000,
        'bid_days' => 14,
        // پروژه در انتظار یا باز همزمان برای هر کارفرما؛ ضد انبوه‌نویسی.
        'active_max' => 10,
        'title_min' => 10,
        'title_max' => 120,
        'description_min' => 80,
        'description_max' => 6000,
        'client_name_max' => 80,
        'wanted_days_max' => 365,
        // پیوست خصوصی: فقط کارفرما، مدیر و مجری پس از قرارداد.
        'files_max' => 5,
        'file_max_kb' => 10_240,
        'file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'dwg', 'xlsx', 'docx', 'zip'],
        'files_directory' => 'marketplace/projects',
    ],

    'per_page' => 20,

    // صفحه خدمت یا شهر با کمتر از این تعداد پروژه باز noindex و بیرون از نقشه سایت است (مثل DEC-59).
    'index_min_projects' => 2,

];
