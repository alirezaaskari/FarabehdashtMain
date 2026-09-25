<?php

declare(strict_types=1);

namespace App\Modules\Expert\Domain;

use App\Models\User;
use App\Modules\Expert\Domain\Enums\QuestionTopic;
use App\Modules\Expert\Domain\Enums\QuestionVisibility;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * پرسشی که کاربر از مشاوران تأییدشده می‌پرسد.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property QuestionTopic $topic
 * @property QuestionVisibility $visibility
 * @property string $title
 * @property string $body
 * @property ReviewStatus $status
 * @property bool $priority
 * @property string|null $review_note
 * @property int|null $reviewed_by
 * @property Carbon|null $published_at
 * @property int|null $accepted_answer_id
 * @property Carbon|null $answered_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, ExpertAnswer> $answers
 */
final class ExpertQuestion extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'topic',
        'visibility',
        'title',
        'body',
        'status',
        'priority',
        'review_note',
        'reviewed_by',
        'published_at',
        'accepted_answer_id',
        'answered_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function asker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<ExpertAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(ExpertAnswer::class, 'question_id');
    }

    /**
     * پرسش‌هایی که همه می‌بینند: تأییدشده و عمومی.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeListed(Builder $query): void
    {
        $query->where('status', ReviewStatus::Published)->where('visibility', QuestionVisibility::Public);
    }

    /**
     * صف پاسخ‌دهنده‌ها و مدیر: اول مشترک Pro، بعد قدیمی‌تر (DEC-40).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeInQueueOrder(Builder $query): void
    {
        $query->orderByDesc('priority')->oldest()->orderBy('id');
    }

    public function isPublic(): bool
    {
        return $this->status === ReviewStatus::Published && $this->visibility === QuestionVisibility::Public;
    }

    public function isAnswered(): bool
    {
        return $this->accepted_answer_id !== null;
    }

    /**
     * بندهای متن، برای نمایش و موتور پیوند.
     *
     * @return list<string>
     */
    public function paragraphs(): array
    {
        return self::split($this->body);
    }

    /** @return list<string> */
    public static function split(string $text): array
    {
        $parts = preg_split('/\R{2,}/u', trim($text)) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn (string $part): bool => $part !== ''));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'topic' => QuestionTopic::class,
            'visibility' => QuestionVisibility::class,
            'status' => ReviewStatus::class,
            'priority' => 'boolean',
            'published_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }
}
