<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * تکمیل یک جلسه توسط دانشجو — فقط افزودنی.
 *
 * @property int $id
 * @property int $enrollment_id
 * @property int $course_session_id
 * @property Carbon $completed_at
 */
final class SessionProgress extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'enrollment_id',
        'course_session_id',
        'completed_at',
    ];

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return BelongsTo<CourseSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    /** تکمیل یک جلسه هرگز لغو نمی‌شود. */
    public function delete(): bool
    {
        throw new RuntimeException('تکمیل جلسه فقط افزودنی است؛ حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }
}
