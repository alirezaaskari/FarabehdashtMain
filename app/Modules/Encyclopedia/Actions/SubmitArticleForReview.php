<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Actions;

use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use RuntimeException;

/**
 * پیش‌نویس آماده بازبینی علمی است و در صف تأیید می‌آید.
 *
 * شرط انتشار این‌جا بررسی نمی‌شود: نویسنده می‌تواند محتوای بدون بازبین را
 * بفرستد، چون بازبین همان کسی است که قرار است آن را بخواند.
 */
final readonly class SubmitArticleForReview
{
    public function handle(Article $article): Article
    {
        if ($article->status !== ArticleStatus::Draft) {
            throw new RuntimeException('فقط پیش‌نویس برای بازبینی فرستاده می‌شود.');
        }

        if ($article->sections()->count() === 0) {
            throw new RuntimeException('محتوای بدون بخش برای بازبینی فرستاده نمی‌شود.');
        }

        $article->forceFill(['status' => ArticleStatus::InReview])->save();

        return $article->refresh();
    }
}
