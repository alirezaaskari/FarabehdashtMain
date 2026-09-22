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
 * سه ابزار نخست مرکز ابزارها، برای صفحه اصلی.
 *
 * فقط ابزارهای قابل استفاده: ابزاری که مدیر خاموشش کرده روی صفحه اصلی تبلیغ
 * نمی‌شود تا کاربر به صفحه‌ای برسد که کار نمی‌کند.
 */
final readonly class ToolHighlights implements HomepageSource
{
    private const LIMIT = 3;

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
                meta: sprintf('منبع: %s · نسخه %s', $tool->formula->reference()->title, $tool->version()),
            ),
            array_slice($this->catalog->usable(), 0, self::LIMIT),
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
}
