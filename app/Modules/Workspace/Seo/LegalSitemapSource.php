<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use App\Modules\Workspace\Services\LegalLibrary;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * صفحات حقوقی در نقشه سایت: فقط نسخه جاری، و فقط سندی که نسخه منتشرشده دارد.
 *
 * نسخه‌های قدیمی noindex‌اند و وضعیت سرویس و جست‌وجو هم؛ هیچ‌کدام اینجا نمی‌آیند.
 */
final readonly class LegalSitemapSource implements SitemapSource
{
    public function __construct(private LegalLibrary $library) {}

    public function section(): string
    {
        return 'pages';
    }

    /** @return iterable<SitemapUrl> */
    public function sitemapUrls(): iterable
    {
        if (! Route::has('workspace.legal.show')) {
            return;
        }

        foreach (LegalDocument::cases() as $document) {
            $current = $this->library->current($document);

            if ($current !== null) {
                yield new SitemapUrl(route('workspace.legal.show', $document->value), $current->effective_at);
            }
        }
    }
}
