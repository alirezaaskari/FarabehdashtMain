<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Modules\ExamPrep\Domain\Enums\PackStatus;
use App\Modules\ExamPrep\Domain\Enums\QuestionStatus;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Domain\PackTopic;
use App\Modules\ExamPrep\Domain\PrepChoice;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Modules\ExamPrep\Services\QuestionDraft;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * نوشتن یک سؤال در یک بسته. سؤال مدرس در صف تأیید مدیر می‌ماند؛ سؤالی که
 * مدیر خودش از CSV وارد می‌کند همان لحظه منتشر است.
 *
 * نمونه رایگان هر بسته سقف دارد (DEC-46)؛ سؤال در انتظار هم در سقف شمرده
 * می‌شود تا تأیید بعدی سقف را نشکند.
 */
final readonly class AddQuestion
{
    public function __construct(
        private ConnectionInterface $db,
        private int $sampleSize,
    ) {}

    public function handle(ExamPack $pack, QuestionDraft $draft, ?int $authorId, bool $publish): PrepQuestion
    {
        if ($pack->status === PackStatus::Retired) {
            throw new InvalidArgumentException('این بسته بایگانی شده و سؤال تازه نمی‌گیرد.');
        }

        $topic = PackTopic::query()->where('exam_pack_id', $pack->id)->where('title', $draft->topic)->first()
            ?? throw new InvalidArgumentException(sprintf('موضوع «%s» در این بسته نیست.', $draft->topic));

        return $this->db->transaction(function () use ($pack, $draft, $authorId, $publish, $topic): PrepQuestion {
            if ($draft->isSample && $this->samplesTaken($pack) >= $this->sampleSize) {
                throw new InvalidArgumentException(sprintf('نمونه رایگان این بسته پر است (%d سؤال).', $this->sampleSize));
            }

            $question = PrepQuestion::query()->create([
                'uuid' => (string) Str::uuid7(),
                'exam_pack_id' => $pack->id,
                'topic_id' => $topic->id,
                'author_user_id' => $authorId,
                'difficulty' => $draft->difficulty,
                'body' => $draft->body,
                'explanation' => $draft->explanation,
                'reference_label' => $draft->referenceLabel,
                'reference_path' => $draft->referencePath,
                'is_sample' => $draft->isSample,
                'status' => $publish ? QuestionStatus::Published : QuestionStatus::Pending,
                'published_at' => $publish ? now() : null,
            ]);

            foreach ($draft->choices as $sort => $body) {
                PrepChoice::query()->create([
                    'question_id' => $question->id,
                    'body' => $body,
                    'is_correct' => $sort === $draft->correctIndex,
                    'sort' => $sort,
                ]);
            }

            return $question;
        });
    }

    private function samplesTaken(ExamPack $pack): int
    {
        return PrepQuestion::query()
            ->where('exam_pack_id', $pack->id)
            ->where('is_sample', true)
            ->whereIn('status', [QuestionStatus::Published->value, QuestionStatus::Pending->value])
            ->lockForUpdate()
            ->count();
    }
}
