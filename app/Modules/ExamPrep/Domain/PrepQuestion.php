<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain;

use App\Models\User;
use App\Modules\ExamPrep\Domain\Enums\Difficulty;
use App\Modules\ExamPrep\Domain\Enums\QuestionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک سؤال چهارگزینه‌ای (دو تا پنج گزینه، فقط یکی درست).
 *
 * `reference_path` نشانی داخلی سایت است (مقاله، ابزار یا دوره) که کارنامه
 * زیر پاسخ غلط نشانش می‌دهد؛ نشانی بیرونی پذیرفته نمی‌شود.
 *
 * @property int $id
 * @property string $uuid
 * @property int $exam_pack_id
 * @property int $topic_id
 * @property int|null $author_user_id
 * @property Difficulty $difficulty
 * @property string $body
 * @property string|null $explanation
 * @property string|null $reference_label
 * @property string|null $reference_path
 * @property bool $is_sample
 * @property QuestionStatus $status
 * @property string|null $review_note
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class PrepQuestion extends Model
{
    protected $fillable = [
        'uuid',
        'exam_pack_id',
        'topic_id',
        'author_user_id',
        'difficulty',
        'body',
        'explanation',
        'reference_label',
        'reference_path',
        'is_sample',
        'status',
        'review_note',
        'published_at',
    ];

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

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    /** @return HasMany<PrepChoice, $this> */
    public function choices(): HasMany
    {
        return $this->hasMany(PrepChoice::class, 'question_id')->orderBy('sort')->orderBy('id');
    }

    /** @param  Builder<self>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', QuestionStatus::Published->value);
    }

    public function correctChoice(): ?PrepChoice
    {
        return $this->choices->firstWhere('is_correct', true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'difficulty' => Difficulty::class,
            'status' => QuestionStatus::class,
            'is_sample' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
