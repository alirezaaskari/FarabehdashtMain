<?php

declare(strict_types=1);

namespace App\Modules\Expert\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * پرسش و پاسخ منتظر تصمیم مدیر، برای صف یکپارچه داشبورد پنل.
 * پرسش مشترک Pro اول می‌آید (DEC-40).
 */
final readonly class PendingExpertItems implements ApprovalQueueSource
{
    public const ABILITY = 'admin.content.review';

    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $url = Route::has('filament.fbh.pages.expert-review') ? route('filament.fbh.pages.expert-review') : url('/');

        $questions = ExpertQuestion::query()->where('status', ReviewStatus::Pending)->inQueueOrder()->cursor();

        foreach ($questions as $question) {
            yield new PendingItem(
                ability: self::ABILITY,
                kind: 'expert_question',
                title: ($question->priority ? 'پرسش Pro — ' : 'پرسش — ').Str::limit($question->title, 80),
                url: $url,
                waitingSince: $question->created_at,
            );
        }

        foreach (ExpertAnswer::query()->where('status', ReviewStatus::Pending)->with('question')->oldest()->cursor() as $answer) {
            yield new PendingItem(
                ability: self::ABILITY,
                kind: 'expert_answer',
                title: 'پاسخ — '.Str::limit($answer->question->title, 80),
                url: $url,
                waitingSince: $answer->updated_at,
            );
        }
    }
}
