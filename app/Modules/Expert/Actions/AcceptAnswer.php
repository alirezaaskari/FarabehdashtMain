<?php

declare(strict_types=1);

namespace App\Modules\Expert\Actions;

use App\Models\User;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Modules\Expert\Events\AnswerAccepted;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * پرسش‌کننده بهترین پاسخ را علامت می‌زند و پرسش «پاسخ‌گرفته» می‌شود.
 * می‌تواند نظرش را عوض کند و پاسخ دیگری را برگزیند.
 */
final readonly class AcceptAnswer
{
    public function __construct(private Dispatcher $events) {}

    public function handle(User $asker, ExpertQuestion $question, ExpertAnswer $answer): ExpertQuestion
    {
        if ($question->user_id !== $asker->getKey()) {
            throw new RuntimeException('فقط پرسش‌کننده بهترین پاسخ را انتخاب می‌کند.');
        }

        if ($answer->question_id !== $question->id || $answer->status !== ReviewStatus::Published) {
            throw new RuntimeException('این پاسخ برای انتخاب در دسترس نیست.');
        }

        if ($question->accepted_answer_id === $answer->id) {
            return $question;
        }

        $question->forceFill([
            'accepted_answer_id' => $answer->id,
            'answered_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new AnswerAccepted($question, $answer));

        return $question;
    }
}
