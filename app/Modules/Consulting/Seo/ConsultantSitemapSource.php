<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * فهرست مشاوران و صفحه هر مشاوری که منتشر شده و پنهان نیست.
 */
final readonly class ConsultantSitemapSource implements SitemapSource
{
    public function section(): string
    {
        return 'consultants';
    }

    public function sitemapUrls(): iterable
    {
        if (! Route::has('consulting.show')) {
            return;
        }

        yield new SitemapUrl(route('consulting.index'));

        foreach (ConsultantProfile::query()->listed()->orderBy('id')->cursor() as $profile) {
            yield new SitemapUrl(loc: route('consulting.show', $profile->slug), lastmod: $profile->reviewed_at ?? $profile->updated_at);
        }
    }
}
