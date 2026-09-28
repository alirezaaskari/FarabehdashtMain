<?php

declare(strict_types=1);

/*
 * آموزش متحرک کنار راهنمای «chemicals» (شکل تعریف: App\Support\Help\Motion\MotionTutorials).
 */

return [
    'title' => 'پیدا کردن ماده و حد مواجهه آن',
    'url' => 'farabehdasht.com/chemicals',
    'intro' => [
        'kicker' => 'بانک مواد شیمیایی',
        'heading' => 'حد مواجهه هر ماده، با مرجعش',
        'art' => 'motion-chemicals-intro',
        'caption' => 'بانک مواد شیمیایی مشخصات هر ماده را با شماره CAS، حد مواجهه از چند مرجع، راه‌های ورود و حفاظت نگه می‌دارد.',
    ],
    'scenes' => [
        [
            'chapter' => 'جست‌وجو',
            'caption' => 'نام فارسی، نام انگلیسی یا شماره CAS را بنویسید. با دکمه‌های گروه، همه مواد یک گروه مثل حلال‌ها را هم می‌بینید.',
            'widgets' => [
                ['type' => 'search', 'value' => '[[108-88-3]]'],
                ['type' => 'choice', 'marker' => 'none', 'pick' => 0, 'items' => [
                    ['تولوئن', '[[Toluene · 108-88-3]]'],
                ]],
                ['type' => 'text', 'text' => 'گروه‌ها: حلال‌ها · فلزات · گازها'],
            ],
        ],
        [
            'chapter' => 'حد مواجهه',
            'url' => 'farabehdasht.com/chemicals/toluene',
            'caption' => 'در صفحه ماده، حد مواجهه شغلی هر مرجع با واحد و منبعش آمده است. عدد را با همان مرجعی مقایسه کنید که کارفرما یا قانون از شما می‌خواهد.',
            'widgets' => [
                ['type' => 'heading', 'text' => 'تولوئن', 'sub' => 'شماره CAS: [[108-88-3]] · جرم مولکولی: [[92.14]]'],
                ['type' => 'table', 'head' => ['مرجع', 'نوع', 'مقدار'], 'rows' => [
                    ['[[ACGIH]]', '[[TLV-TWA]]', '[[20 ppm]]'],
                    ['[[NIOSH]]', '[[REL-TWA]]', '[[100 ppm]]'],
                    ['[[OSHA]]', '[[PEL-TWA]]', '[[200 ppm]]'],
                ]],
            ],
        ],
        [
            'chapter' => 'محاسبه با این ماده',
            'url' => 'farabehdasht.com/chemicals/toluene',
            'caption' => 'از بخش «محاسبه با این ماده» ابزار تبدیل با جرم مولکولی همین ماده باز می‌شود؛ لازم نیست عدد را دستی بنویسید.',
            'widgets' => [
                ['type' => 'heading', 'text' => 'محاسبه با این ماده'],
                ['type' => 'button', 'label' => 'تبدیل ppm به mg/m³'],
                ['type' => 'fields', 'items' => [
                    ['label' => 'جرم مولکولی (g/mol)', 'value' => '[[92.14]]'],
                    ['label' => 'غلظت (ppm)', 'value' => '62', 'typed' => true],
                ]],
            ],
        ],
        [
            'chapter' => 'مقایسه و تاریخچه',
            'url' => 'farabehdasht.com/chemicals/compare',
            'caption' => 'چند ماده را کنار هم مقایسه کنید و در «تاریخچه» ببینید حد هر مرجع کی و چطور عوض شده است.',
            'widgets' => [
                ['type' => 'table', 'head' => ['ماده', '[[ACGIH TLV-TWA]]'], 'rows' => [
                    ['تولوئن', '[[20 ppm]]'],
                    ['زایلن', '[[20 ppm]]'],
                    ['استون', '[[250 ppm]]'],
                ]],
                ['type' => 'button', 'label' => 'تاریخچه تغییر حدها', 'variant' => 'secondary'],
            ],
        ],
    ],
    'outro' => [
        'kicker' => 'یادتان باشد',
        'heading' => 'حد مجاز راهنماست، نه تأیید ایمنی',
        'caption' => 'حد مواجهه برای مقایسه است، نه تأیید قطعی ایمنی. همیشه مرجع و تاریخ آخرین بررسی را در صفحه ماده ببینید.',
    ],
];
