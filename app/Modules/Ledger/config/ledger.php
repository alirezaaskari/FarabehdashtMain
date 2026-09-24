<?php

declare(strict_types=1);

/*
| شرح فارسی هر نوع تراکنش، برای گردش کیف پول کاربر.
|
| کلیدها همان `kind` است که هر ماژول هنگام ثبت تراکنش می‌گذارد. نوعی که این‌جا
| نیست با شرح عمومی «تراکنش کیف پول» نشان داده می‌شود، نه با کلید خام.
*/

return [

    'kind_labels' => [
        'wallet.manual_topup' => 'شارژ کیف پول توسط پشتیبانی',
        'commerce.order_paid' => 'خرید از فروشگاه',
        'commerce.refund_issued' => 'بازگشت وجه خرید',
        'courses.enrollment_paid' => 'ثبت‌نام در دوره',
        'monetization.subscription_paid' => 'پرداخت اشتراک حرفه‌ای',
    ],

];
