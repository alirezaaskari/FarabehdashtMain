<?php

declare(strict_types=1);

namespace App\Modules\Expert\Domain;

use App\Models\User;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * پاسخ یک مشاور تأییدشده. پیش از دیده‌شدن، مدیر تأییدش می‌کند.
 *
 * @property int $id
 * @property string $uuid
 * @property int $question_id
 * @property int $user_id
 * @property string $body
 * @property ReviewStatus $status
 * @property string|null $review_note
 * @property int|null $reviewed_by
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ExpertQuestion $question
 * @property-read User $answerer
 */
final class ExpertAnswer extends Model
{
    protected $fillable = [
        'uuid',
        'question_id',
        'user_id',
        'body',
        'status',
        'review_note',
        'reviewed_by',
        'published_at',
    ];

    /** @return BelongsTo<ExpertQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(ExpertQuestion::class, 'question_id');
    }

    /** @return BelongsTo<User, $this> */
    public function answerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ReviewStatus::Published);
    }

    /**
     * نامی که زیر پاسخ می‌آید. شماره موبایل هرگز جای نام خالی را نمی‌گیرد.
     */
    public function answererName(): string
    {
        $name = trim((string) $this->answerer->name);

        return $name !== '' ? $name : 'مشاور فرابهداشت';
    }

    /** @return list<string> */
    public function paragraphs(): array
    {
        return ExpertQuestion::split($this->body);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
