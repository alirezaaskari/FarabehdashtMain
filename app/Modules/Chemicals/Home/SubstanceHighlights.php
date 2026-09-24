<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Home;

use App\Contracts\HomepageSource;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\Home\HomeItem;
use App\Support\Home\HomeLayout;
use App\Support\Home\HomeSection;
use Illuminate\Support\Facades\Route;

/**
 * چند ماده پرمراجعه بانک مواد، برای صفحه اصلی.
 *
 * `meta` شماره CAS است و نه فرمول: کاربر بانک مواد با CAS جست‌وجو می‌کند.
 */
final readonly class SubstanceHighlights implements HomepageSource
{
    private const LIMIT = 5;

    public function homeSection(): ?HomeSection
    {
        if (! Route::has('chemicals.show') || ! Route::has('chemicals.index')) {
            return null;
        }

        $substances = Substance::query()
            ->published()
            ->orderBy('name_fa')
            ->limit(self::LIMIT)
            ->get();

        $items = $substances->map(fn (Substance $substance): HomeItem => new HomeItem(
            title: $substance->name_fa,
            url: route('chemicals.show', $substance->slug),
            kicker: $substance->name_en ?? '',
            meta: $substance->cas_number,
        ))->all();

        return new HomeSection(
            key: 'chemicals',
            title: 'بانک مواد شیمیایی',
            lede: 'جست‌وجو بر اساس نام فارسی، نام انگلیسی، مترادف یا شماره CAS — همراه با حدود مواجهه چند مرجع، مسیرهای مواجهه و روش نمونه‌برداری.',
            items: $items,
            order: 30,
            moreUrl: route('chemicals.index'),
            moreLabel: 'ورود به بانک مواد',
            layout: HomeLayout::Panel,
            searchUrl: route('chemicals.index'),
            searchPlaceholder: 'مثلاً: تولوئن یا 108-88-3',
        );
    }
}
