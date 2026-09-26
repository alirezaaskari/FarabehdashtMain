<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain;

use App\Modules\ExamPrep\Domain\Enums\AttemptMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک دور نمونه، تمرین یا آزمون. ترتیب سؤال‌ها همان لحظه شروع ثابت می‌شود
 * تا بازگشت به صفحه سؤال‌ها را جابه‌جا نکند.
 *
 * @property int $id
 * @property string $uuid
 * @property int $exam_pack_id
 * @property int $user_id
 * @property AttemptMode $mode
 * @property int|null $topic_id
 * @property list<int> $question_ids
 * @property Carbon|null $deadline_at
 * @property Carbon|null $submitted_at
 * @property bool $late
 * @property int $correct_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class PrepAttempt extends Model
{
    protected $fillable = [
        'uuid',
        'exam_pack_id',
        'user_id',
        'mode',
        'topic_id',
        'question_ids',
        'deadline_at',
        'submitted_at',
        'late',
        'correct_count',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<ExamPack, $this> */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(ExamPack::class, 'exam_pack_id');
    }

    /** @return BelongsTo<PackTopic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(PackTopic::class, 'topic_id');
    }

    /** @return HasMany<PrepAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(PrepAnswer::class, 'attempt_id');
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function total(): int
    {
        return count($this->question_ids);
    }

    /** سؤال بعدی بی‌پاسخ در حالت پاسخ فوری، یا null اگر همه پاسخ گرفته‌اند. */
    public function nextQuestionId(): ?int
    {
        $answered = $this->answers->pluck('question_id')->all();

        foreach ($this->question_ids as $id) {
            if (! in_array($id, $answered, true)) {
                return $id;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'mode' => AttemptMode::class,
            'question_ids' => 'array',
            'deadline_at' => 'datetime',
            'submitted_at' => 'datetime',
            'late' => 'boolean',
        ];
    }
}
