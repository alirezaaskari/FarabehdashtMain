<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Services\HomePage;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

/**
 * صفحه اصلی.
 *
 * هیچ منطقی ندارد جز صدازدن سرویس (قاعده ۴): چه چیزی نشان داده شود را
 * ماژول‌ها تعیین می‌کنند، نه این کنترلر.
 */
final readonly class HomeController
{
    public function __invoke(HomePage $page): View
    {
        return view('core::home', ['rows' => $page->rows(), 'seo' => $this->seo()]);
    }

    private function seo(): SeoMeta
    {
        $meta = new SeoMeta(
            title: 'میزکار متخصص بهداشت حرفه‌ای',
            description: 'دانشنامه بازبینی‌شده، ابزارهای محاسباتی با منبع علمی، بانک مواد شیمیایی، و فایل‌ها و دوره‌های تخصصی بهداشت حرفه‌ای و ایمنی کار.',
            canonical: route('home'),
        );

        // جست‌وجوی داخلی مال ماژول Workspace است و ممکن است خاموش باشد.
        $search = Route::has('workspace.search') ? route('workspace.search').'?q={search_term_string}' : null;

        return $meta->withSchema(Schema::graph(Schema::organization(), Schema::website($search)));
    }
}
