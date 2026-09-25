<?php

declare(strict_types=1);

namespace App\Modules\Expert\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * فقط پرسش‌های تأییدشده و عمومی؛ پرسش خصوصی نه این‌جاست نه در جست‌وجو (DEC-41).
 */
final readonly class QuestionSitemapSource implements SitemapSource
{
    public function section(): string
    {
        return 'expert';
    }

    public function sitemapUrls(): iterable
    {
        if (! Route::has('expert.show')) {
            return;
        }

        yield new SitemapUrl(route('expert.index'));

        foreach (ExpertQuestion::query()->listed()->orderBy('id')->cursor() as $question) {
            yield new SitemapUrl(
                loc: route('expert.show', $question->uuid),
                lastmod: $question->answered_at ?? $question->updated_at,
            );
        }
    }
}
