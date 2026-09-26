<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $attempt_id
 * @property int $question_id
 * @property int|null $choice_id
 * @property bool $is_correct
 */
final class PrepAnswer extends Model
{
    protected $fillable = ['attempt_id', 'question_id', 'choice_id', 'is_correct'];

    /** @return BelongsTo<PrepAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PrepAttempt::class, 'attempt_id');
    }

    /** @return BelongsTo<PrepQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(PrepQuestion::class, 'question_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
