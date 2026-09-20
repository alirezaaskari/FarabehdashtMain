<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Actions;

use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Events\ArticlePublished;
use App\Support\PersianDigits;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * انتشار یک محتوا.
 *
 * این اکشن تنها دری است که وضعیت به «منتشرشده» می‌رسد، و دلیلش معیار پذیرش
 * این بخش است: **محتوای بدون بازبین منتشر نمی‌شود.**
 *
 * سه شرط، و هر سه پیش از نوشتن بررسی می‌شوند:
 *
 * ۱. بازبین علمی و تاریخ بازبینی هر دو ثبت شده باشند. یکی بدون دیگری بی‌معناست.
 * ۲. دست‌کم به اندازه‌ای که نوع محتوا می‌گوید، منبع **نسخه‌دار** داشته باشد.
 *    منبع بدون ویرایش و سال، به دو سند متفاوت اشاره می‌کند.
 * ۳. دست‌کم یک بخش داشته باشد. صفحه خالی نشانی عمومی نمی‌گیرد.
 *
 * موعد بازبینی همین‌جا از روی دوره نوع محتوا حساب و **ذخیره** می‌شود. اگر
 * به‌جای ذخیره هر بار محاسبه می‌شد، تغییر دوره در کد، موعد صدها محتوای
 * منتشرشده را بی‌خبر جابه‌جا می‌کرد.
 */
final readonly class PublishArticle
{
    public function __construct(
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    /**
     * @throws RuntimeException اگر محتوا آماده انتشار نباشد
     */
    public function handle(Article $article, ?int $actorId = null, ?Carbon $now = null): Article
    {
        $this->guard($article);

        $now ??= Carbon::now();

        $published = $this->db->transaction(function () use ($article, $now): Article {
            $article->forceFill([
                'status' => ArticleStatus::Published,
                'published_at' => $article->published_at ?? $now,
                'review_due_at' => $article->reviewed_at?->copy()
                    ->addMonths($article->type->reviewIntervalMonths()),
            ])->save();

            return $article->refresh();
        });

        $this->events->dispatch(new ArticlePublished($published, $actorId));

        return $published;
    }

    /**
     * @throws RuntimeException
     */
    private function guard(Article $article): void
    {
        if (! $article->isReviewed()) {
            throw new RuntimeException(
                'این محتوا بازبین علمی یا تاریخ بازبینی ندارد و منتشر نمی‌شود.',
            );
        }

        $versioned = $article->references()
            ->get()
            ->filter(static fn (ArticleReference $reference): bool => $reference->isVersioned())
            ->count();

        $needed = $article->type->minimumReferences();

        if ($versioned < $needed) {
            throw new RuntimeException(sprintf(
                'برای «%s» دست‌کم %s منبع نسخه‌دار لازم است؛ اکنون %s منبع ثبت شده.',
                $article->type->label(),
                PersianDigits::from($needed),
                PersianDigits::from($versioned),
            ));
        }

        if ($article->sections()->count() === 0) {
            throw new RuntimeException('محتوای بدون بخش منتشر نمی‌شود.');
        }
    }
}
