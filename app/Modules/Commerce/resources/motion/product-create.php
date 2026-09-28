<?php

declare(strict_types=1);

/*
 * آموزش متحرک کنار راهنمای «product-create» (شکل تعریف: App\Support\Help\Motion\MotionTutorials).
 */

return [
    'title' => 'ساخت محصول برای فروشگاه',
    'url' => 'farabehdasht.com/commerce/vendor/products/create',
    'steps' => ['مشخصات', 'فایل', 'بررسی'],
    'intro' => [
        'kicker' => 'برای فروشنده‌ها',
        'heading' => 'فرم، قالب یا چک‌لیست خودتان را بفروشید',
        'art' => 'motion-product-intro',
        'caption' => 'ساخت محصول سه قدم دارد: مشخصات و قیمت، بارگذاری فایل، و فرستادن برای بررسی مدیر.',
    ],
    'scenes' => [
        [
            'chapter' => 'مشخصات',
            'step' => 0,
            'caption' => 'عنوان روشن بنویسید تا خریدار در جست‌وجو پیدایش کند. قیمت به تومان است و تا پیش از انتشار عوض می‌شود.',
            'widgets' => [
                ['type' => 'fields', 'items' => [
                    ['label' => 'عنوان', 'value' => 'فرم‌های ارزیابی ریسک به روش HAZOP', 'typed' => true, 'full' => true],
                    ['label' => 'قیمت (تومان)', 'value' => '۱۵۰٬۰۰۰', 'typed' => true],
                ]],
                ['type' => 'textarea', 'label' => 'توضیح', 'value' => 'شش فرم آماده با راهنمای پر کردن.'],
                ['type' => 'button', 'label' => 'ساخت محصول'],
            ],
        ],
        [
            'chapter' => 'فایل',
            'step' => 1,
            'caption' => 'فایل‌ها را در صفحه محصول بارگذاری کنید. خریدار پس از پرداخت از «خریدهای من» دانلودشان می‌کند.',
            'widgets' => [
                ['type' => 'upload', 'label' => 'فایل محصول', 'file' => 'hazop-forms.docx', 'result' => 'بارگذاری شد'],
                ['type' => 'upload', 'label' => 'نسخه PDF', 'file' => 'hazop-forms.pdf', 'result' => 'بارگذاری شد'],
            ],
        ],
        [
            'chapter' => 'بررسی',
            'step' => 2,
            'caption' => '«ارسال برای بررسی» را بزنید. محصول تا تأیید مدیر در فروشگاه دیده نمی‌شود؛ پس از هر فروش سهم شما به مانده‌تان اضافه می‌شود.',
            'widgets' => [
                ['type' => 'button', 'label' => 'ارسال برای بررسی'],
                ['type' => 'track', 'items' => ['پیش‌نویس', 'در انتظار بررسی', 'در فروشگاه']],
            ],
        ],
    ],
    'outro' => [
        'kicker' => 'فروش و تسویه',
        'heading' => 'گزارش فروش و درخواست تسویه در میزکار شماست',
        'caption' => 'گزارش فروش و درخواست تسویه در میزکار شماست؛ واریز به شبای خودتان انجام می‌شود.',
    ],
];
