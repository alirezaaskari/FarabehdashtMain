<?php

declare(strict_types=1);

namespace App\Modules\Tools\Home;

use App\Contracts\HomepageSource;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\Home\HomeItem;
use App\Support\Home\HomeSection;
use Illuminate\Support\Facades\Route;

/**
 * شش ابزار برای صفحه اصلی: اول یکی از هر گروه، بعد بقیه به ترتیب مرکز.
 *
 * شش ابزار نخست فهرست همه از دو گروه‌اند؛ صفحه اصلی باید گستره ابزارها را
 * نشان دهد، نه فقط گروه اول را.
 *
 * فقط ابزارهای قابل استفاده: ابزاری که مدیر خاموشش کرده روی صفحه اصلی تبلیغ
 * نمی‌شود تا کاربر به صفحه‌ای برسد که کار نمی‌کند.
 */
final readonly class ToolHighlights implements HomepageSource
{
    private const LIMIT = 6;

    public function __construct(private ToolCatalog $catalog) {}

    public function homeSection(): ?HomeSection
    {
        if (! Route::has('tools.show') || ! Route::has('tools.index')) {
            return null;
        }

        $items = array_map(
            fn (ResolvedTool $tool): HomeItem => new HomeItem(
                title: $tool->definition->title,
                url: route('tools.show', $tool->slug()),
                kicker: $tool->definition->category->label(),
                summary: $tool->definition->summary,
                meta: sprintf('منبع: %s · نسخه %s', $tool->formula->reference()->title, $tool->displayVersion()),
                icon: $tool->definition->category->icon(),
            ),
            $this->pick(),
        );

        return new HomeSection(
            key: 'tools',
            title: 'مرکز ابزارهای تخصصی',
            lede: 'محاسبه با فرمول نسخه‌دار، ذخیره نتیجه در میزکار، و خروجی قابل چاپ.',
            items: $items,
            order: 10,
            moreUrl: route('tools.index'),
            moreLabel: 'همه ابزارها',
        );
    }

    /** @return list<ResolvedTool> */
    private function pick(): array
    {
        $first = [];
        $rest = [];

        foreach ($this->catalog->grouped() as $group) {
            $first[] = $group['tools'][0];
            array_push($rest, ...array_slice($group['tools'], 1));
        }

        return array_slice([...$first, ...$rest], 0, self::LIMIT);
    }
}
