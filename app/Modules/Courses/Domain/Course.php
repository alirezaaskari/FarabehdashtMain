<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain;

use App\Models\User;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * یک دوره آموزشی متعلق به یک مدرس.
 *
 * @property int $id
 * @property string $uuid
 * @property int $instructor_user_id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property int $price_toman
 * @property CourseStatus $status
 * @property string|null $review_note
 * @property Carbon|null $reviewed_at
 * @property int|null $reviewed_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Course extends Model
{
    protected $fillable = [
        'uuid',
        'instructor_user_id',
        'slug',
        'title',
        'description',
        'price_toman',
        'status',
        'review_note',
        'reviewed_at',
        'reviewed_by',
    ];

    /** @return BelongsTo<User, $this> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<CourseSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class)->orderBy('position');
    }

    /** @return HasOne<Exam, $this> */
    public function exam(): HasOne
    {
        return $this->hasOne(Exam::class);
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    /** دوره رایگان (DEC-38) مثل دوره پولی تأیید مدیر می‌خواهد، فقط پرداخت ندارد. */
    public function isFree(): bool
    {
        return $this->price()->isZero();
    }

    public function priceLabel(): string
    {
        return $this->isFree() ? 'رایگان' : $this->price()->format();
    }

    /**
     * دوره منتشرشده‌ای که جلسه یا سؤال تازه‌اش منتظر تأیید مدیر است.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeWithPendingChanges(Builder $query): void
    {
        $query->where('status', CourseStatus::Published->value)
            ->where(static function (Builder $query): void {
                $query->whereHas('sessions', static fn (Builder $session) => $session->whereNull('approved_at'))
                    ->orWhereHas('exam.questions', static fn (Builder $question) => $question->whereNull('approved_at'));
            });
    }

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', CourseStatus::Published->value);
    }

    /** @param  Builder<$this>  $query */
    public function scopeOwnedBy(Builder $query, int $instructorUserId): void
    {
        $query->where('instructor_user_id', $instructorUserId);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'price_toman' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }
}
