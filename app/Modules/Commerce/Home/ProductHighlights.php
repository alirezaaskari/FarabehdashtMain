<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Home;

use App\Contracts\HomepageSource;
use App\Modules\Commerce\Domain\Product;
use App\Support\Home\HomeItem;
use App\Support\Home\HomeLayout;
use App\Support\Home\HomeSection;
use Illuminate\Support\Facades\Route;

/**
 * تازه‌ترین فایل‌های تأییدشده فروشگاه، برای صفحه اصلی.
 *
 * قیمت از `Money` می‌آید و نه از ستون خام (قاعده ۶)، تا تومان در یک جا
 * قالب‌بندی شود.
 */
final readonly class ProductHighlights implements HomepageSource
{
    private const LIMIT = 1;

    public function homeSection(): ?HomeSection
    {
        if (! Route::has('commerce.show') || ! Route::has('commerce.index')) {
            return null;
        }

        $products = Product::query()
            ->published()
            ->orderByDesc('reviewed_at')
            ->limit(self::LIMIT)
            ->get();

        $items = $products->map(fn (Product $product): HomeItem => new HomeItem(
            title: $product->title,
            url: route('commerce.show', $product->slug),
            kicker: 'فایل تخصصی',
            summary: $product->description ?? '',
            meta: $product->price()->format(),
        ))->all();

        return new HomeSection(
            key: 'commerce',
            title: 'فایل‌ها و فرم‌های آماده',
            lede: 'فرم، چک‌لیست و قالب گزارش؛ هر فایل پیش از انتشار بررسی می‌شود و به‌روزرسانی نسخه‌ها برای خریداران رایگان است.',
            items: $items,
            order: 50,
            art: 'scene.shop',
            moreUrl: route('commerce.index'),
            moreLabel: 'فروشگاه',
            layout: HomeLayout::Tile,
            icon: 'file',
        );
    }
}
