<?php

declare(strict_types=1);

namespace App\Support\Workspace;

/**
 * یک کارت میزکار، همان‌طور که ماژول صاحبش آن را می‌دهد.
 *
 * شکل ثابت است — چند آمار بالا، چند ردیف پایین — تا قالب میزکار روی نام
 * ماژول‌ها `if` نزند. `order` ترتیب پروتوتایپ است، نه ترتیب ثبت ماژول‌ها.
 *
 * `empty` متن حالت خالی است: کارتی که ردیف ندارد باز هم نشان داده می‌شود،
 * چون «هنوز دوره‌ای شروع نکرده‌اید» خودش راهنمای قدم بعدی است. `icon` نام
 * آیکن ردیف «دسترسی سریع» است.
 */
final readonly class WorkspaceWidget
{
    /**
     * @param  list<WidgetStat>  $stats
     * @param  list<WidgetRow>  $rows
     */
    public function __construct(
        public string $key,
        public string $title,
        public int $order,
        public array $stats = [],
        public array $rows = [],
        public ?string $empty = null,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $icon = null,
    ) {}
}
