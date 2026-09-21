<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک تلاش برای آزمون — فقط افزودنی؛ دانشجو می‌تواند دوباره تلاش کند.
 *
 * @property int $id
 * @property int $enrollment_id
 * @property int $exam_id
 * @property int $score_percentage
 * @property bool $passed
 * @property Carbon $created_at
 */
final class ExamAttempt extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'enrollment_id',
        'exam_id',
        'score_percentage',
        'passed',
    ];

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return BelongsTo<Exam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('تلاش ثبت‌شده آزمون تغییرناپذیر است.');
    }

    public function delete(): bool
    {
        throw new RuntimeException('تلاش آزمون فقط افزودنی است؛ حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'score_percentage' => 'integer',
            'passed' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
