<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain;

use App\Models\User;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * ثبت‌نام یک دانشجو در یک دوره — قیمت و کمیسیون Snapshot لحظه خرید.
 *
 * @property int $id
 * @property string $uuid
 * @property int $course_id
 * @property int $student_user_id
 * @property EnrollmentStatus $status
 * @property int $price_toman
 * @property int $commission_rate_bp
 * @property int $commission_toman
 * @property int $instructor_amount_toman
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property PaymentSource $payment_source
 * @property Carbon|null $paid_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Enrollment extends Model
{
    protected $fillable = [
        'uuid',
        'course_id',
        'student_user_id',
        'status',
        'price_toman',
        'commission_rate_bp',
        'commission_toman',
        'instructor_amount_toman',
        'gateway_authority',
        'gateway_ref_id',
        'payment_source',
        'paid_at',
        'completed_at',
    ];

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    /** @return HasMany<SessionProgress, $this> */
    public function progress(): HasMany
    {
        return $this->hasMany(SessionProgress::class);
    }

    /** @return HasMany<ExamAttempt, $this> */
    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /** @return HasOne<CourseReview, $this> */
    public function review(): HasOne
    {
        return $this->hasOne(CourseReview::class);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    public function instructorAmount(): Money
    {
        return Money::toman($this->instructor_amount_toman);
    }

    public function commission(): Money
    {
        return Money::toman($this->commission_toman);
    }

    public function hasCompletedSession(int $courseSessionId): bool
    {
        return $this->progress->contains('course_session_id', $courseSessionId);
    }

    public function bestExamScore(): ?int
    {
        return $this->examAttempts->max('score_percentage');
    }

    public function hasPassedExam(): bool
    {
        return $this->examAttempts->contains('passed', true);
    }

    /** @param  Builder<$this>  $query */
    public function scopeForStudent(Builder $query, int $studentUserId): void
    {
        $query->where('student_user_id', $studentUserId);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'payment_source' => PaymentSource::class,
            'price_toman' => 'integer',
            'commission_rate_bp' => 'integer',
            'commission_toman' => 'integer',
            'instructor_amount_toman' => 'integer',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
