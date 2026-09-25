<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;

/**
 * محتوای منتظر تصمیم مدیر.
 *
 * دو چیز در صف می‌آید و هر دو واقعاً معطل‌اند:
 *
 * ۱. محتوای «در انتظار بازبینی» — کسی باید بازبینی کند.
 * ۲. محتوای منتشرشده‌ای که موعد بازبینی‌اش گذشته — روی سایت است و شاید عقب.
 *
 * پیش‌نویس در صف نمی‌آید: هنوز کسی آن را نفرستاده و معطل مدیر نیست.
 */
final readonly class PendingArticles implements ApprovalQueueSource
{
    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $url = Route::has('filament.fbh.pages.content-health')
            ? route('filament.fbh.pages.content-health')
            : url('/');

        $editable = Route::has('filament.fbh.resources.articles.edit');

        foreach (Article::query()->where('status', ArticleStatus::InReview->value)->cursor() as $article) {
            yield new PendingItem(
                ability: 'admin.content.review',
                kind: 'article',
                title: $article->type->label().' — '.$article->title,
                url: $editable ? route('filament.fbh.resources.articles.edit', ['record' => $article]) : $url,
                waitingSince: $article->updated_at,
            );
        }

        $overdue = Article::query()
            ->where('status', ArticleStatus::Published->value)
            ->whereNotNull('review_due_at')
            ->where('review_due_at', '<', now())
            ->cursor();

        foreach ($overdue as $article) {
            yield new PendingItem(
                ability: 'admin.content.review',
                kind: 'article_review_due',
                title: 'موعد بازبینی گذشته — '.$article->title,
                url: $url,
                waitingSince: $article->review_due_at,
            );
        }
    }
}
