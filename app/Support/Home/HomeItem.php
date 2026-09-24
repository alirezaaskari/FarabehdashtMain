<?php

declare(strict_types=1);

namespace App\Support\Home;

/**
 * یک ردیف در بخش‌های صفحه اصلی — و فقط رشته.
 *
 * مثل `ToolSummary`، این تنها شکلی است که محتوای یک ماژول روی صفحه اصلی دیده
 * می‌شود. اگر روزی شیء دامنه از این در عبور کند، صفحه اصلی به آن ماژول وابسته
 * می‌شود و قاعده ۱ نازک می‌شود.
 */
final readonly class HomeItem
{
    public function __construct(
        public string $title,
        public string $url,
        public string $kicker,
        public string $summary = '',
        public ?string $meta = null,
        public ?string $icon = null,
    ) {}
}
