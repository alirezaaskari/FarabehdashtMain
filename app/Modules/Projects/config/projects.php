<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| پروژه‌ها و تجهیزات
|--------------------------------------------------------------------------
*/

return [

    /*
    | چند روز مانده به پایان اعتبار کالیبراسیون، تجهیز «نزدیک انقضا» شمرده
    | شود. کارشناسی که فردا می‌رود میدان باید امروز بداند.
    */
    'calibration_warning_days' => 30,

    /*
    | چند روز آینده در تقویم الزامات پایش نمایش داده شود.
    */
    'calendar_horizon_days' => 180,

    /*
    | قالب پروژه بر اساس صنعت.
    |
    | ⚠️ فهرست هر قالب **پیشنهادی و عمومی** است. تعیین دامنه واقعی پایش بر
    | عهده کارشناس و بر اساس شناسایی عوامل زیان‌آور همان واحد است. این‌جا فقط
    | نقطه شروع است تا کسی از صفر شروع نکند.
    |
    | `tools` فقط شناسه است و این ماژول هرگز حلش نمی‌کند؛ اگر ماژول ابزارها
    | خاموش باشد، پیوندها نمایش داده نمی‌شوند و چیزی نمی‌شکند (قاعده ۱).
    */
    'templates' => [

        'foundry' => [
            'stations' => ['کوره ذوب', 'خط ریخته‌گری', 'ماسه‌سازی', 'شات‌بلاست', 'اتاق کنترل'],
            'tools' => ['wbgt-indoor', 'sound-pressure-sum', 'noise-dose', 'hand-arm-vibration', 'illuminance-uniformity'],
            'note' => 'استرس گرمایی کنار کوره و صدای شات‌بلاست معمولاً دو عامل غالب‌اند.',
        ],

        'petrochemical' => [
            'stations' => ['واحد فرآیند', 'اتاق کنترل', 'مخازن', 'پکیج کمپرسور', 'آزمایشگاه'],
            'tools' => ['twa-ppm', 'mixture-exposure-index-ppm', 'brief-scala-adjustment', 'noise-dose', 'wbgt-outdoor'],
            'note' => 'مواجهه با بخارات آلی و صدای کمپرسور. واحدهای باز، رابطه WBGT آفتابی می‌خواهند.',
        ],

        'hospital' => [
            'stations' => ['اتاق عمل', 'بخش بستری', 'استریلیزاسیون', 'آزمایشگاه', 'موتورخانه'],
            'tools' => ['twa-ppm', 'illuminance-uniformity', 'air-changes-per-hour'],
            'note' => 'گازهای بیهوشی و مواد ضدعفونی، روشنایی وظیفه دقیق، و تعویض هوای اتاق عمل.',
        ],

        'automotive' => [
            'stations' => ['پرس', 'بدنه‌سازی', 'رنگ', 'مونتاژ', 'تست نهایی'],
            'tools' => ['daily-noise-exposure', 'twa-mass-concentration', 'hand-arm-vibration', 'niosh-lifting', 'illuminance-uniformity'],
            'note' => 'صدای ضربه‌ای پرس و بخارات سالن رنگ.',
        ],

        'mining' => [
            'stations' => ['جبهه‌کار', 'سنگ‌شکن', 'نوار نقاله', 'کارگاه تعمیرات'],
            'tools' => ['twa-mass-concentration', 'noise-dose', 'whole-body-vibration', 'air-changes-per-hour', 'wbgt-indoor'],
            'note' => 'گرد و غبار قابل تنفس و صدا. برای غبار، تبدیل ppm معنا ندارد.',
        ],

        'textile' => [
            'stations' => ['ریسندگی', 'بافندگی', 'رنگرزی', 'انبار'],
            'tools' => ['sound-pressure-sum', 'noise-dose', 'illuminance-uniformity', 'wbgt-indoor'],
            'note' => 'صدای پیوسته بافندگی و رطوبت و گرمای رنگرزی.',
        ],

        'construction' => [
            'stations' => ['گودبرداری', 'اسکلت', 'جوشکاری', 'نازک‌کاری'],
            'tools' => ['wbgt-outdoor', 'daily-noise-exposure', 'twa-mass-concentration', 'hand-arm-vibration', 'niosh-lifting'],
            'note' => 'کار در فضای باز؛ رابطه WBGT با بار تابشی خورشید.',
        ],

        'printing' => [
            'stations' => ['چاپخانه', 'صحافی', 'انبار حلال', 'اتاق رنگ'],
            'tools' => ['twa-ppm', 'mixture-exposure-index-ppm', 'dilution-ventilation', 'air-changes-per-hour', 'illuminance-uniformity'],
            'note' => 'حلال‌های چاپ و کفایت تهویه موضعی.',
        ],

    ],

];
