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
 * روی نام ماژول‌ها `if` بزند و دوباره به آن‌ها وابسته شود.
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
    ) {}
}
