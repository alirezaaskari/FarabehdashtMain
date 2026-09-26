<?php

declare(strict_types=1);

namespace App\Support\Home;

/**
 * یک بخش از صفحه اصلی، همان‌طور که ماژول صاحبش آن را می‌دهد.
 *
 * `order` ترتیب پروتوتایپ است، نه ترتیب ثبت ماژول‌ها: با خاموش‌شدن یک ماژول
 * بقیه بخش‌ها جابه‌جا نمی‌شوند و چیدمان صفحه اصلی ثابت می‌ماند.
 *
 * `layout` شکل نمایش را خود ماژول اعلام می‌کند؛ وگرنه قالب Core مجبور می‌شود
 * روی نام ماژول‌ها `if` بزند و دوباره به آن‌ها وابسته شود. `searchUrl` کادر
 * جست‌وجوی خود بخش است (پارامتر `q`)، برای چیدمان پنل.
 *
 * `feature` یک کارت برجسته آخر ردیف کارت‌هاست (مثلاً دستیار انتخاب ابزار).
 * بخش بی‌ردیف به صفحه نمی‌رسد، مگر `keepWhenEmpty`: کاشی‌ای که خودش دعوت
 * به کاری است (پرسش از متخصص) حتی بی‌محتوای تازه هم معنا دارد. `icon` نشان
 * کاشی است.
 */
final readonly class HomeSection
{
    /** @param  list<HomeItem>  $items */
    public function __construct(
        public string $key,
        public string $title,
        public string $lede,
        public array $items,
        public int $order,
        public ?string $moreUrl = null,
        public ?string $moreLabel = null,
        public HomeLayout $layout = HomeLayout::Cards,
        public ?string $searchUrl = null,
        public ?string $searchPlaceholder = null,
        public ?HomeItem $feature = null,
        public bool $keepWhenEmpty = false,
        public ?string $icon = null,
    ) {}
}
