<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_question_id
 * @property string $text
 * @property bool $is_correct
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ExamChoice extends Model
{
    protected $fillable = [
        'exam_question_id',
        'text',
        'is_correct',
    ];

    /** @return BelongsTo<ExamQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'exam_question_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }
}
