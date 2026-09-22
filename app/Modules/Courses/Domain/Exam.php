<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * آزمون یک دوره — حداکثر یکی به ازای هر دوره.
 *
 * @property int $id
 * @property int $course_id
 * @property int $pass_percentage
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Exam extends Model
{
    protected $fillable = [
        'course_id',
        'pass_percentage',
    ];

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return HasMany<ExamQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('position');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'pass_percentage' => 'integer',
        ];
    }
}
