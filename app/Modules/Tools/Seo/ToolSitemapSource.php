<?php

declare(strict_types=1);

namespace App\Modules\Tools\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * نشانی‌های ابزارها برای نقشه سایت.
 *
 * فقط ابزار قابل استفاده؛ ابزار خاموش ۴۰۴ می‌دهد و نشانی‌اش در نقشه خطای
 * Search Console می‌سازد. `lastmod` ندارد: تعریف ابزار در پیکربندی است و
 * تاریخ تغییر معناداری ندارد، و تاریخ ساختگی بدتر از نبودنش است.
 */
final readonly class ToolSitemapSource implements SitemapSource
{
    public function __construct(private ToolCatalog $catalog) {}

    public function section(): string
    {
        return 'tools';
    }

    /** @return iterable<SitemapUrl> */
    public function sitemapUrls(): iterable
    {
        if (! Route::has('tools.show')) {
            return;
        }

        yield new SitemapUrl(route('tools.index'));

        if (Route::has('tools.advisor')) {
            yield new SitemapUrl(route('tools.advisor'));
        }

        foreach ($this->catalog->usable() as $tool) {
            yield new SitemapUrl(route('tools.show', $tool->slug()));
        }
    }
}
