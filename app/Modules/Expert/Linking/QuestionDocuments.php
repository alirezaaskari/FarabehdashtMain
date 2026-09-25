<?php

declare(strict_types=1);

namespace App\Modules\Expert\Linking;

use App\Contracts\LinkableContentSource;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Support\Linking\LinkableDocument;
use Illuminate\Support\Facades\Route;

/**
 * پرسش‌های عمومی و پاسخ‌های منتشرشده‌شان، به‌عنوان متنی که موتور پیوند
 * داخلی در آن به ابزار، ماده و مقاله پیوند می‌گذارد.
 *
 * ترتیب بندها همان ترتیب صفحه است: پرسش، بعد پاسخ پذیرفته، بعد بقیه.
 * پرسش خصوصی هرگز این‌جا نمی‌آید.
 */
final readonly class QuestionDocuments implements LinkableContentSource
{
    public static function key(ExpertQuestion $question): string
    {
        return 'expert:'.$question->uuid;
    }

    public function linkableDocuments(): iterable
    {
        if (! Route::has('expert.show')) {
            return;
        }

        $questions = ExpertQuestion::query()
            ->listed()
            ->with(['answers' => static fn ($query) => $query->published()->oldest('published_at')])
            ->orderBy('id')
            ->lazy(50);

        foreach ($questions as $question) {
            $answers = $question->answers->sortByDesc(
                static fn (ExpertAnswer $answer): bool => $answer->id === $question->accepted_answer_id,
            );

            yield new LinkableDocument(
                self::key($question),
                $question->title,
                route('expert.show', $question->uuid),
                array_merge(
                    $question->paragraphs(),
                    ...$answers->map(static fn (ExpertAnswer $answer): array => $answer->paragraphs())->values()->all(),
                ),
            );
        }
    }
}
