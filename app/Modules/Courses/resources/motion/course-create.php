<?php

declare(strict_types=1);

/*
 * آموزش متحرک کنار راهنمای «course-create» (شکل تعریف: App\Support\Help\Motion\MotionTutorials).
 */

return [
    'title' => 'ساخت دوره آموزشی',
    'url' => 'farabehdasht.com/courses/instructor/courses/create',
    'steps' => ['مشخصات', 'جلسه‌ها', 'بررسی'],
    'intro' => [
        'kicker' => 'برای مدرس‌ها',
        'heading' => 'دوره بسازید، جلسه‌ها را بچینید، برای بررسی بفرستید',
        'art' => 'motion-course-intro',
        'caption' => 'ساخت دوره سه قدم دارد: مشخصات و قیمت، جلسه‌ها و آزمون، و فرستادن برای بررسی مدیر.',
    ],
    'scenes' => [
        [
            'chapter' => 'مشخصات',
            'step' => 0,
            'caption' => 'عنوان، توضیح و قیمت را بنویسید. قیمت صفر یعنی دوره رایگان؛ دوره رایگان هم پیش از انتشار تأیید مدیر می‌خواهد.',
            'widgets' => [
                ['type' => 'fields', 'items' => [
                    ['label' => 'عنوان دوره', 'value' => 'ایمنی کار با مواد شیمیایی', 'typed' => true, 'full' => true],
                    ['label' => 'قیمت (تومان)', 'value' => '۴۹۰٬۰۰۰', 'typed' => true, 'full' => true],
                ]],
                ['type' => 'button', 'label' => 'ساخت دوره'],
            ],
        ],
        [
            'chapter' => 'جلسه‌ها',
            'step' => 1,
            'url' => 'farabehdasht.com/courses/instructor/courses/chemical-safety',
            'caption' => 'جلسه‌ها را بعد از ساخت اضافه کنید و ترتیبشان را عوض کنید. آزمون پایانی هم همین‌جا اضافه می‌شود.',
            'widgets' => [
                ['type' => 'choice', 'marker' => 'none', 'items' => [
                    ['۱. برگه اطلاعات ایمنی چیست', '۱۲ دقیقه'],
                    ['۲. راه‌های ورود به بدن', '۱۵ دقیقه'],
                    ['۳. انتخاب دستکش و ماسک', '۱۸ دقیقه'],
                ]],
                ['type' => 'button', 'label' => 'افزودن جلسه', 'variant' => 'secondary'],
                ['type' => 'button', 'label' => 'افزودن آزمون پایانی', 'variant' => 'secondary'],
            ],
        ],
        [
            'chapter' => 'بررسی',
            'step' => 2,
            'caption' => 'دوره را برای بررسی بفرستید. تا تأیید مدیر برای دانشجو دیده نمی‌شود؛ پس از انتشار، فروش‌ها در گزارش فروش مدرس می‌آید.',
            'widgets' => [
                ['type' => 'button', 'label' => 'ارسال برای بررسی'],
                ['type' => 'track', 'items' => ['پیش‌نویس', 'در انتظار بررسی', 'منتشرشده']],
            ],
        ],
    ],
    'outro' => [
        'kicker' => 'پس از انتشار',
        'heading' => 'دانشجویی که دوره را تمام کند، در گذرنامه‌اش ثبت می‌شود',
        'caption' => 'دوره‌ای که تا آخر دیده شود در گذرنامه مهارت دانشجو ثبت می‌شود؛ گواهی رسمی نیست.',
    ],
];
