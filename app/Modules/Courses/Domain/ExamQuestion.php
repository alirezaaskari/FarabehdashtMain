<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_id
 * @property string $text
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ExamQuestion extends Model
{
    protected $fillable = [
        'exam_id',
        'text',
        'position',
    ];

    /** @return BelongsTo<Exam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return HasMany<ExamChoice, $this> */
    public function choices(): HasMany
    {
        return $this->hasMany(ExamChoice::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
