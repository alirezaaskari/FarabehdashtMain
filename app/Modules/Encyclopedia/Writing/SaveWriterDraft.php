<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Writing;

use App\Models\User;
use App\Modules\Encyclopedia\Actions\SaveArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * ذخیره پیش‌نویس نویسنده از میزکار، از همان مسیر {@see SaveArticle} پنل.
 *
 * نویسنده فقط پیش‌نویس خودش را و فقط تا وقتی پیش‌نویس است ویرایش می‌کند؛
 * بازبین، تاریخ بازبینی و نشانی مال مدیر است و از این مسیر عوض نمی‌شود.
 * نکته و ابزاری که مدیر به بخشی افزوده، با همان عنوان بخش نگه داشته می‌شود.
 */
final readonly class SaveWriterDraft
{
    public function __construct(private SaveArticle $save) {}

    /**
     * @param  array{title: string, type: string, summary: string, body: string, references?: string|null}  $input
     */
    public function handle(User $writer, ?Article $article, array $input): Article
    {
        if ($article !== null && ! self::canEdit($writer, $article)) {
            throw new RuntimeException('این نوشته دیگر پیش‌نویس شما نیست و از این‌جا ویرایش نمی‌شود.');
        }

        $existing = $article?->sections->keyBy('heading') ?? collect();

        $sections = array_map(static function (array $section) use ($existing): array {
            $kept = $existing->get($section['heading']);

            return [
                ...$section,
                'note' => $kept instanceof ArticleSection ? $kept->note : null,
                'tool_slug' => $kept instanceof ArticleSection ? $kept->tool_slug : null,
            ];
        }, DraftText::sections($input['body']));

        if ($sections === []) {
            throw new RuntimeException('متن نوشته خالی است.');
        }

        return $this->save->handle($article, [
            'title' => $input['title'],
            'type' => ArticleType::from($input['type']),
            'summary' => $input['summary'],
            // نشانی موقت و یکتا؛ مدیر پیش از انتشار نشانی خوانا می‌گذارد.
            'slug' => $article?->slug ?? 'draft-'.Str::lower(Str::random(10)),
            'reviewer_id' => $article?->reviewer_id,
            'reviewed_at' => $article?->reviewed_at,
            'sections' => $sections,
            'references' => DraftText::references((string) ($input['references'] ?? '')),
        ], (int) $writer->getKey());
    }

    public static function canEdit(User $writer, Article $article): bool
    {
        return $article->author_id === $writer->getKey() && $article->status === ArticleStatus::Draft;
    }
}
