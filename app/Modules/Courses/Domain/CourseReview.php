<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * دیدگاه دانشجو پس از تکمیل دوره — فقط افزودنی.
 *
 * @property int $id
 * @property int $enrollment_id
 * @property int $rating
 * @property string|null $comment
 * @property Carbon $created_at
 */
final class CourseReview extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'enrollment_id',
        'rating',
        'comment',
    ];

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('دیدگاه ثبت‌شده تغییرناپذیر است.');
    }

    public function delete(): bool
    {
        throw new RuntimeException('دیدگاه فقط افزودنی است؛ حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
