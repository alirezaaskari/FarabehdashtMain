<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\ProfileType;

return [

    /*
    |--------------------------------------------------------------------------
    | رمز یک‌بارمصرف
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ورود مدیر با ایمیل
    |--------------------------------------------------------------------------
    | فقط برای حسابی که به پنل مدیریت راه دارد و ایمیلش با
    | `fbh:make-admin --email` تأیید شده. کد همان قاعده‌های پیامک را دارد، با
    | عمر بیشتر چون ایمیل دیرتر از پیامک می‌رسد. ارسال با تنظیمات MAIL_* است.
    */

    'admin_email_login' => [
        'panel' => 'fbh',
        'ttl_seconds' => 600,
    ],

    'otp' => [
        'length' => 4,
        'ttl_seconds' => 120,

        /** سقف تلاش برای یک کد؛ پس از آن کد سوخته است. */
        'max_attempts' => 5,

        /** فاصله لازم تا درخواست کد بعدی. */
        'resend_after_seconds' => 90,

        /** سقف درخواست کد از یک شماره در یک ساعت. */
        'max_requests_per_hour' => 5,

        /** سقف درخواست کد از یک IP در یک ساعت — جلوی پویش انبوه را می‌گیرد. */
        'max_requests_per_ip_per_hour' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | پیامک
    |--------------------------------------------------------------------------
    |
    | در محیط محلی درایور log کافی است؛ کد در لاگ نوشته می‌شود.
    | درایور melipayamak در محیط واقعی استفاده می‌شود.
    |
    */

    'sms' => [
        'driver' => env('FBH_SMS_DRIVER', 'log'),

        /*
        | خط عمومی: متن آزاد می‌پذیرد ولی به گوشی‌هایی که پیامک تبلیغاتی را
        | مسدود کرده‌اند نمی‌رسد. برای رمز ورود مناسب نیست.
        */
        'melipayamak' => [
            'username' => env('MELIPAYAMAK_USERNAME'),
            'password' => env('MELIPAYAMAK_PASSWORD'),
            'from' => env('MELIPAYAMAK_FROM'),
            'endpoint' => env('MELIPAYAMAK_ENDPOINT', 'https://rest.payamak-panel.com/api/SendSMS/SendSMS'),
            'timeout' => 10,
        ],

        /*
        | خط خدماتی با الگو: انتخاب درست برای رمز یک‌بارمصرف. متن آزاد
        | نمی‌پذیرد؛ فقط شناسه الگوی تأییدشده و مقدار متغیرها.
        |
        | `patterns` کلید الگو را به شناسه‌اش در پنل ملی‌پیامک نگاشت می‌کند.
        | الگوی `otp` باید دقیقاً **یک** متغیر داشته باشد: خود کد.
        |   نمونه متن الگو در پنل:  کد ورود شما به فرابهداشت: {0}
        */
        'melipayamak_pattern' => [
            'username' => env('MELIPAYAMAK_USERNAME'),
            'password' => env('MELIPAYAMAK_PASSWORD'),
            'endpoint' => env(
                'MELIPAYAMAK_PATTERN_ENDPOINT',
                'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber',
            ),
            'separator' => env('MELIPAYAMAK_PATTERN_SEPARATOR', ';'),
            'timeout' => 10,
            'patterns' => [
                'otp' => env('MELIPAYAMAK_PATTERN_OTP'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | مجوزها
    |--------------------------------------------------------------------------
    |
    | قاعده محصول: مجوزهای کاربر برابر است با اتحاد مجوزهای پایه و مجوزهای
    | همه پروفایل‌های فعال او. پروفایل غیرفعال هیچ مجوزی اضافه نمی‌کند و هیچ
    | داده‌ای را هم حذف نمی‌کند.
    |
    */

    'permissions' => [

        /** هر کاربر احرازشده، صرف‌نظر از نقش‌هایش. */
        'base' => [
            'tools.use',
            'tools.save_calculation',
            'content.read',
            'chemicals.read',
            'market.purchase',
            'courses.enroll',
            'wallet.view',
            'jobs.browse',
            'jobs.apply',
        ],

        /**
         * برچسب فارسی هر مجوز، برای نمایش به کاربر.
         * کلید بدون برچسب، همان کلید فنی نشان داده می‌شود.
         */
        'labels' => [
            'tools.use' => 'استفاده از ابزارهای محاسبه',
            'tools.save_calculation' => 'ذخیره محاسبه',
            'content.read' => 'مطالعه محتوای علمی',
            'chemicals.read' => 'جست‌وجو در بانک مواد شیمیایی',
            'market.purchase' => 'خرید از فروشگاه',
            'courses.enroll' => 'ثبت‌نام در دوره',
            'wallet.view' => 'مشاهده کیف پول',
            'jobs.browse' => 'دیدن آگهی‌های شغلی',
            'jobs.apply' => 'ارسال درخواست استخدام',

            'resume.manage' => 'مدیریت رزومه',
            'jobs.alerts' => 'هشدار شغلی',
            'jobs.applications.manage' => 'پیگیری درخواست‌های استخدام',

            'jobs.post' => 'ثبت آگهی شغلی',
            'jobs.applicants.manage' => 'مدیریت متقاضیان',
            'jobs.notes.write' => 'یادداشت روی متقاضی',

            'products.manage' => 'مدیریت فایل‌ها و قالب‌ها',
            'products.submit_for_review' => 'ارسال محصول برای بررسی',
            'sales.reports' => 'گزارش فروش',
            'settlement.request' => 'درخواست تسویه',

            'courses.manage' => 'مدیریت دوره‌ها',
            'courses.submit_for_review' => 'ارسال دوره برای بررسی',

            'consulting.services.manage' => 'مدیریت خدمات مشاوره',
            'consulting.requests.manage' => 'مدیریت درخواست‌های مشاوره',
            'expert.answer' => 'پاسخ به پرسش تخصصی',

            'content.write' => 'نوشتن پیش‌نویس دانشنامه',
        ],

        'profiles' => [
            ProfileType::Jobseeker->value => [
                'resume.manage',
                'jobs.alerts',
                'jobs.applications.manage',
            ],

            ProfileType::Employer->value => [
                'jobs.post',
                'jobs.applicants.manage',
                'jobs.notes.write',
            ],

            ProfileType::Vendor->value => [
                'products.manage',
                'products.submit_for_review',
                'sales.reports',
                'settlement.request',
            ],

            ProfileType::Instructor->value => [
                'courses.manage',
                'courses.submit_for_review',
                'sales.reports',
                'settlement.request',
            ],

            ProfileType::Consultant->value => [
                'consulting.services.manage',
                'consulting.requests.manage',
                'expert.answer',
            ],

            // نویسنده فقط پیش‌نویس می‌نویسد؛ ویرایش نهایی و انتشار با مدیر محتواست.
            ProfileType::Writer->value => [
                'content.write',
            ],
        ],
    ],

];
