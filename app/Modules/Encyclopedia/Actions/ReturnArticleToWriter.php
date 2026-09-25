<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Actions;

use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Events\ArticleReturnedToWriter;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * مدیر محتوای در انتظار بازبینی را با یادداشت به پیش‌نویس برمی‌گرداند تا
 * نویسنده اصلاحش کند. یادداشت اجباری است: «رد شد» بدون دلیل، نویسنده را
 * بی‌راه می‌گذارد.
 */
final readonly class ReturnArticleToWriter
{
    public function __construct(private Dispatcher $events) {}

    public function handle(Article $article, string $note, ?int $actorId): Article
    {
        $note = trim($note);

        if ($note === '') {
            throw new RuntimeException('برای بازگرداندن، یادداشتی بنویسید که نویسنده بداند چه چیزی را اصلاح کند.');
        }

        if ($article->status !== ArticleStatus::InReview) {
            throw new RuntimeException('فقط محتوای در انتظار بازبینی بازگردانده می‌شود.');
        }

        $article->forceFill(['status' => ArticleStatus::Draft, 'review_note' => $note])->save();

        $this->events->dispatch(new ArticleReturnedToWriter($article, $actorId));

        return $article->refresh();
    }
}
