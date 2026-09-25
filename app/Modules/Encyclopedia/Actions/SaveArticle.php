<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Actions;

use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use App\Modules\Encyclopedia\Events\ArticleSaved;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * ساخت یا ویرایش یک محتوا از ویرایشگر پنل.
 *
 * بخش‌ها و منابع با هر ذخیره **از نو نوشته می‌شوند**، نه تک‌به‌تک به‌روز: جای
 * هر ردیف (`position`) یکتاست و جابه‌جایی دو بخش با به‌روزرسانی ردیفی از همان
 * قید عبور نمی‌کند. شناسه ردیف بخش جایی نگه داشته نمی‌شود؛ لنگر از شماره
 * ساخته می‌شود و پیوندها پس از ذخیره بازسازی می‌شوند.
 *
 * وضعیت این‌جا عوض نمی‌شود. محتوای تازه پیش‌نویس است و انتشار فقط از
 * {@see PublishArticle} می‌گذرد. نشانی محتوای منتشرشده ثابت می‌ماند، چون
 * پیوندهای بیرونی و ذخیره‌شده کاربران به آن اشاره دارند.
 */
final readonly class SaveArticle
{
    public function __construct(
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data  همان داده فرم پنل: عنوان، نشانی، نوع، خلاصه، بازبین،
     *                                      تاریخ بازبینی، و فهرست‌های `sections` و `references`
     */
    public function handle(?Article $article, array $data, ?int $actorId): Article
    {
        $created = $article === null;

        $saved = $this->db->transaction(function () use ($article, $data, $actorId): Article {
            $article ??= new Article([
                'uuid' => (string) Str::uuid7(),
                'status' => ArticleStatus::Draft,
                'author_id' => $actorId,
            ]);

            $article->fill([
                'title' => (string) self::nullableString($data['title'] ?? null),
                'type' => $data['type'] instanceof ArticleType ? $data['type'] : ArticleType::from((string) $data['type']),
                'summary' => (string) self::nullableString($data['summary'] ?? null),
                'reviewer_id' => self::nullableInt($data['reviewer_id'] ?? null),
                'reviewed_at' => self::date($data['reviewed_at'] ?? null),
            ]);

            $slug = self::nullableString($data['slug'] ?? null);

            if ($article->status !== ArticleStatus::Published && $slug !== null) {
                $article->slug = Str::lower($slug);
            }

            $article->save();

            $article->sections()->delete();
            $article->sections()->createMany($this->sections(self::list($data['sections'] ?? [])));

            $article->references()->delete();
            $article->references()->createMany($this->references(self::list($data['references'] ?? [])));

            return $article->refresh()->load(['sections', 'references']);
        });

        $this->events->dispatch(new ArticleSaved($saved, $actorId, $created));

        return $saved;
    }

    /**
     * @param  array<array-key, array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function sections(array $sections): array
    {
        $rows = [];

        foreach (array_values($sections) as $index => $section) {
            $rows[] = [
                'position' => $index + 1,
                'heading' => (string) self::nullableString($section['heading'] ?? null),
                'body' => (string) self::nullableString($section['body'] ?? null),
                'note' => self::nullableString($section['note'] ?? null),
                'tool_slug' => self::nullableString($section['tool_slug'] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<array-key, array<string, mixed>>  $references
     * @return list<array<string, mixed>>
     */
    private function references(array $references): array
    {
        $rows = [];

        foreach (array_values($references) as $index => $reference) {
            $rows[] = [
                'position' => $index + 1,
                'title' => (string) self::nullableString($reference['title'] ?? null),
                'publisher' => self::nullableString($reference['publisher'] ?? null),
                'edition' => self::nullableString($reference['edition'] ?? null),
                'year' => self::nullableInt($reference['year'] ?? null),
                'url' => self::nullableString($reference['url'] ?? null),
            ];
        }

        return $rows;
    }

    /** @return array<array-key, array<string, mixed>> */
    private static function list(mixed $items): array
    {
        return is_array($items) ? array_filter($items, is_array(...)) : [];
    }

    private static function date(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        $text = self::nullableString($value);

        return $text === null ? null : Carbon::parse($text);
    }

    private static function blank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private static function nullableString(mixed $value): ?string
    {
        return self::blank($value) || ! is_scalar($value) ? null : trim((string) $value);
    }

    private static function nullableInt(mixed $value): ?int
    {
        return self::blank($value) || ! is_numeric($value) ? null : (int) $value;
    }
}
