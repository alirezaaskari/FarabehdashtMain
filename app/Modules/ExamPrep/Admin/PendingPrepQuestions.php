<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\ExamPrep\Domain\Enums\QuestionStatus;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/** سؤال‌هایی که مدرسان نوشته‌اند و منتظر تأیید مدیرند. */
final readonly class PendingPrepQuestions implements ApprovalQueueSource
{
    public const ABILITY = 'admin.content.review';

    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $url = Route::has('filament.fbh.pages.exam-questions') ? route('filament.fbh.pages.exam-questions') : url('/');

        $questions = PrepQuestion::query()->where('status', QuestionStatus::Pending)->with('pack')->oldest()->cursor();

        foreach ($questions as $question) {
            yield new PendingItem(
                ability: self::ABILITY,
                kind: 'exam_question',
                title: 'سؤال آزمون — '.$question->pack->title.': '.Str::limit($question->body, 60),
                url: $url,
                waitingSince: $question->created_at,
            );
        }
    }
}
