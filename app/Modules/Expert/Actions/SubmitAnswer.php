<?php

declare(strict_types=1);

namespace App\Modules\Expert\Actions;

use App\Models\User;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Modules\Expert\Events\AnswerSubmitted;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * پاسخ مشاور، در انتظار تأیید مدیر (قاعده محتوای مشاور).
 *
 * هر مشاور یک پاسخ به هر پرسش دارد. پاسخ ردشده با همین کار اصلاح و دوباره
 * فرستاده می‌شود؛ پاسخ در انتظار یا منتشرشده دست نمی‌خورد.
 */
final readonly class SubmitAnswer
{
    public const ABILITY = 'expert.answer';

    public function __construct(private Dispatcher $events) {}

    public function handle(User $answerer, ExpertQuestion $question, string $body): ExpertAnswer
    {
        if (! $answerer->can(self::ABILITY)) {
            throw new RuntimeException('فقط مشاور تأییدشده پاسخ می‌دهد.');
        }

        if ($question->status !== ReviewStatus::Published) {
            throw new RuntimeException('این پرسش هنوز برای پاسخ باز نیست.');
        }

        if ($question->user_id === $answerer->getKey()) {
            throw new RuntimeException('به پرسش خودتان نمی‌توانید پاسخ بدهید.');
        }

        $answer = ExpertAnswer::query()
            ->where('question_id', $question->id)
            ->where('user_id', $answerer->getKey())
            ->first();

        if ($answer !== null && $answer->status !== ReviewStatus::Rejected) {
            throw new RuntimeException('پاسخ شما به این پرسش پیش‌تر فرستاده شده است.');
        }

        $answer ??= new ExpertAnswer([
            'uuid' => (string) Str::uuid7(),
            'question_id' => $question->id,
            'user_id' => $answerer->getKey(),
        ]);

        $answer->forceFill([
            'body' => trim($body),
            'status' => ReviewStatus::Pending,
            'review_note' => null,
            'reviewed_by' => null,
        ])->save();

        $this->events->dispatch(new AnswerSubmitted($answer));

        return $answer;
    }
}
