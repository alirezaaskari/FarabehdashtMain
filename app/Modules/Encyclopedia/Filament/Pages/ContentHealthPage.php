<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Filament\Pages;

use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Filament\Resources\Articles\ArticleResource;
use App\Modules\Encyclopedia\Services\ContentHealth;
use App\Modules\Encyclopedia\Services\Freshness;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * سلامت محتوا و یادآور بازبینی.
 *
 * صفحه تصمیم‌محور است و نه آماری: ترتیب ردیف‌ها بر اساس **نمره سلامت از کم
 * به زیاد** است، یعنی بالای فهرست همان چیزی است که بیشتر از همه به مدیر
 * نیاز دارد.
 *
 * دکمه انتشار همان اکشن `PublishArticle` را صدا می‌زند و نه یک به‌روزرسانی
 * مستقیم؛ اگر قاعده «بدون بازبین منتشر نمی‌شود» از راه پنل دور زدنی بود،
 * اصلاً قاعده نبود.
 */
final class ContentHealthPage extends Page
{
    public const ABILITY = 'admin.content.review';

    protected static ?string $slug = 'content-health';

    protected static ?int $navigationSort = 45;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected string $view = 'encyclopedia::filament.pages.content-health';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public static function getNavigationLabel(): string
    {
        return 'سلامت محتوا';
    }

    public function getTitle(): string
    {
        return 'سلامت محتوا و یادآور بازبینی';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(ContentHealth $health, Freshness $freshness): void
    {
        $this->load($health, $freshness);
    }

    public function publish(int $id, PublishArticle $publish, ContentHealth $health, Freshness $freshness): void
    {
        $article = Article::query()->find($id);

        if ($article === null) {
            return;
        }

        try {
            $publish->handle($article, $this->actorId());

            Notification::make()
                ->title('منتشر شد: '.$article->title)
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('منتشر نشد')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }

        $this->load($health, $freshness);
    }

    private function load(ContentHealth $health, Freshness $freshness): void
    {
        $threshold = (int) config('encyclopedia.health.attention_below', 70);

        $articles = Article::query()
            ->whereIn('status', [ArticleStatus::Draft->value, ArticleStatus::InReview->value, ArticleStatus::Published->value])
            ->with(['reviewer', 'sections', 'references', 'links'])
            ->get();

        $rows = $articles->map(static function (Article $article) use ($health, $freshness, $threshold): array {
            $score = $health->for($article);

            return [
                'id' => $article->id,
                'title' => $article->title,
                'type' => $article->type->label(),
                'status' => $article->status->label(),
                'publishable' => $article->status !== ArticleStatus::Published,
                'editUrl' => ArticleResource::getUrl('edit', ['record' => $article]),
                'reviewer' => $article->reviewer?->name,
                'reviewed' => $article->reviewed_at === null ? null : JalaliDate::short($article->reviewed_at),
                'due' => $article->review_due_at === null ? null : JalaliDate::short($article->review_due_at),
                'daysUntilDue' => $freshness->daysUntilDue($article),
                'freshness' => $freshness->of($article)->label(),
                'score' => $score->score,
                'tone' => $score->tone($threshold),
                'gaps' => $score->gaps,
            ];
        })->all();

        // کم‌نمره‌ترین بالا: صف کار، نه گزارش.
        usort($rows, static fn (array $a, array $b): int => $a['score'] <=> $b['score']);

        $this->rows = $rows;
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
