<?php

declare(strict_types=1);

return [

    /*
    | پیش‌فرض قیمت و مدت آگهی (DEC-63). مدیر هر سه را از صفحه «قیمت آگهی شغلی»
    | پنل عوض می‌کند و از آن پس تنظیم Core مبناست؛ این اعداد فقط روز نصب‌اند.
    */
    'pricing' => [
        'price_toman' => 350_000,
        'days' => 30,
        'first_free' => true,
        // DEC-66: آگهی منقضی تا این چند روز با برچسب «منقضی» و noindex می‌ماند، بعد ۴۱۰.
        'gone_after_days' => 90,
        'days_min' => 7,
        'days_max' => 120,
    ],

    'per_page' => 20,

    // صفحه شهر یا مهارت با کمتر از این تعداد آگهی زنده noindex و بیرون از نقشه سایت است (مثل DEC-59).
    'index_min_postings' => 2,

    'limits' => [
        'name_max' => 120,
        'industry_max' => 80,
        'about_min' => 60,
        'about_max' => 3000,
        'title_max' => 120,
        'description_min' => 120,
        'description_max' => 6000,
        'experience_max' => 30,
        'skills_max' => 8,
        'salary_max_toman' => 1_000_000_000,
        // آگهی باز همزمان برای هر کارفرما؛ ضد انبوه‌نویسی.
        'open_postings_max' => 20,
    ],

    // مدرک ثبت شرکت یا معرفی‌نامه برای بررسی مدیر (DEC-65)؛ خصوصی روی دیسک local.
    'documents' => [
        'max' => 3,
        'max_kb' => 5120,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
        'directory' => 'jobs/documents',
    ],

    /*
    | فهرست آغازین مهارت‌ها (DEC-71). روز نصب در دسته‌بندی «مهارت شغلی» Core
    | کاشته می‌شود و از آن پس مدیر از صفحه «دسته‌بندی‌ها» اضافه یا ویرایشش می‌کند.
    | کلید در نشانی صفحه مهارت می‌آید (/jobs/skill/noise-measurement).
    */
    'skills' => [
        'noise-measurement' => 'اندازه‌گیری و ارزیابی صدا',
        'lighting-measurement' => 'اندازه‌گیری روشنایی',
        'chemical-sampling' => 'نمونه‌برداری عوامل شیمیایی',
        'dust-sampling' => 'نمونه‌برداری گرد و غبار',
        'heat-stress' => 'ارزیابی استرس حرارتی',
        'vibration' => 'اندازه‌گیری ارتعاش',
        'radiation' => 'حفاظت در برابر پرتو',
        'ergonomics' => 'ارزیابی ارگونومی',
        'ventilation' => 'طراحی و ارزیابی تهویه',
        'risk-assessment' => 'ارزیابی ریسک',
        'hazop' => 'مطالعه HAZOP',
        'fire-safety' => 'ایمنی و پیشگیری از حریق',
        'electrical-safety' => 'ایمنی برق',
        'work-at-height' => 'کار در ارتفاع',
        'confined-space' => 'فضای بسته',
        'machine-safety' => 'ایمنی ماشین‌آلات',
        'construction-safety' => 'ایمنی کارگاه ساختمانی',
        'permit-to-work' => 'سیستم مجوز کار',
        'accident-investigation' => 'بررسی حادثه',
        'emergency-response' => 'آمادگی و واکنش در شرایط اضطراری',
        'ppe' => 'انتخاب وسایل حفاظت فردی',
        'hse-ms' => 'سیستم مدیریت HSE',
        'iso-45001' => 'استاندارد ISO 45001',
        'iso-14001' => 'استاندارد ISO 14001',
        'environment' => 'پایش محیط زیست',
        'occupational-medicine' => 'همکاری با طب کار',
        'health-surveillance' => 'پایش سلامت شاغلان',
        'safety-training' => 'آموزش ایمنی و بهداشت کار',
        'hse-reporting' => 'گزارش‌نویسی و مستندسازی HSE',
        'food-hygiene' => 'بهداشت مواد غذایی',
    ],

];
