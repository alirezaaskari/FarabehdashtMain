<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Workspace;

use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Support\PersianDigits;
use App\Support\Workspace\WidgetRow;
use App\Support\Workspace\WidgetStat;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;
use Illuminate\Support\Facades\Route;

/** کارت «نوشته‌های من» روی نمای نویسنده دانشنامه. */
final readonly class WriterWidgets implements WorkspaceWidgetSource
{
    private const LIMIT = 3;

    /** مقدار نوع پروفایل نویسنده — رشته، چون Enum ماژول هویت import نمی‌شود. */
    private const WRITER = 'writer';

    public function widgets(User $user, WorkspaceView $view): array
    {
        if (! $view->is(self::WRITER) || ! Route::has('encyclopedia.writing.index')) {
            return [];
        }

        $query = Article::query()->where('author_id', $user->getKey());

        return [new WorkspaceWidget(
            key: 'writing',
            icon: 'bulb',
            title: 'نوشته‌های دانشنامه',
            order: 10,
            stats: [
                new WidgetStat('در انتظار بازبینی', PersianDigits::from((clone $query)->where('status', ArticleStatus::InReview->value)->count())),
                new WidgetStat('منتشرشده', PersianDigits::from((clone $query)->where('status', ArticleStatus::Published->value)->count())),
            ],
            rows: $query->latest('updated_at')->limit(self::LIMIT)->get()
                ->map(static fn (Article $article): WidgetRow => new WidgetRow(
                    label: $article->title,
                    url: route('encyclopedia.writing.edit', $article->uuid),
                    meta: $article->review_note !== null ? 'برگشت برای اصلاح' : $article->status->label(),
                ))
                ->values()
                ->all(),
            empty: 'نخستین پیش‌نویس را بنویسید؛ پس از بازبینی و تأیید مدیر منتشر می‌شود.',
            actionUrl: route('encyclopedia.writing.create'),
            actionLabel: 'نوشته تازه',
        )];
    }
}
