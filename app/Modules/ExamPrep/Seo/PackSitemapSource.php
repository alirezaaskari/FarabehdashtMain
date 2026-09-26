<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Seo;

use App\Contracts\SitemapSource;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

final readonly class PackSitemapSource implements SitemapSource
{
    public function section(): string
    {
        return 'examprep';
    }

    public function sitemapUrls(): iterable
    {
        if (! Route::has('exam_prep.show')) {
            return;
        }

        yield new SitemapUrl(route('exam_prep.index'));

        foreach (ExamPack::query()->published()->orderBy('id')->cursor() as $pack) {
            yield new SitemapUrl(loc: route('exam_prep.show', $pack->slug), lastmod: $pack->updated_at);
        }
    }
}
