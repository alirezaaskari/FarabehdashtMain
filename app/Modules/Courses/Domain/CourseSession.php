<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * یک جلسه دوره — برخلاف نسخه محصول تجارت، تغییرپذیر است.
 *
 * @property int $id
 * @property int $course_id
 * @property string $title
 * @property string $content_type
 * @property string|null $content
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class CourseSession extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'content_type',
        'content',
        'position',
    ];

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
