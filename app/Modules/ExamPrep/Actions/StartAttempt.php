<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Modules\ExamPrep\Domain\Enums\AttemptMode;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Domain\PackTopic;
use App\Modules\ExamPrep\Domain\PrepAttempt;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Modules\ExamPrep\Services\PackAccess;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * شروع یک دور. نمونه رایگان همان سؤال‌های نشان‌دار «نمونه» است، به ترتیب
 * نوشتن؛ تمرین از موضوع انتخابی (یا همه) و آزمون از کل بانک، هر دو تصادفی.
 */
final readonly class StartAttempt
{
    public function __construct(
        private PackAccess $access,
        private int $sampleSize,
        private int $practiceBatch,
    ) {}

    public function handle(ExamPack $pack, int $userId, AttemptMode $mode, ?PackTopic $topic = null): PrepAttempt
    {
        if (! $pack->isPublished() && ! $this->access->owns($userId, $pack)) {
            throw new InvalidArgumentException('این بسته دیگر در دسترس نیست.');
        }

        if ($mode !== AttemptMode::Sample && ! $this->access->owns($userId, $pack)) {
            throw new InvalidArgumentException('تمرین و آزمون پس از خرید بسته باز می‌شود؛ نمونه رایگان را امتحان کنید.');
        }

        if ($topic !== null && $topic->exam_pack_id !== $pack->id) {
            throw new InvalidArgumentException('این موضوع از بسته دیگری است.');
        }

        $query = PrepQuestion::query()->published()->where('exam_pack_id', $pack->id);

        $ids = match ($mode) {
            AttemptMode::Sample => $query->where('is_sample', true)->orderBy('id')->limit($this->sampleSize)->pluck('id'),
            AttemptMode::Practice => $query->when($topic, fn ($q) => $q->where('topic_id', $topic?->id))
                ->inRandomOrder()->limit($this->practiceBatch)->pluck('id'),
            AttemptMode::Exam => $query->inRandomOrder()->limit($pack->exam_question_count)->pluck('id'),
        };

        if ($ids->isEmpty()) {
            throw new InvalidArgumentException($mode === AttemptMode::Sample
                ? 'این بسته هنوز نمونه رایگان ندارد.'
                : 'در این بخش هنوز سؤالی منتشر نشده است.');
        }

        return PrepAttempt::query()->create([
            'uuid' => (string) Str::uuid7(),
            'exam_pack_id' => $pack->id,
            'user_id' => $userId,
            'mode' => $mode,
            'topic_id' => $mode === AttemptMode::Practice ? $topic?->id : null,
            'question_ids' => $ids->map(static fn (mixed $id): int => (int) $id)->values()->all(),
            'deadline_at' => $mode === AttemptMode::Exam ? now()->addMinutes($pack->exam_minutes) : null,
        ]);
    }
}
