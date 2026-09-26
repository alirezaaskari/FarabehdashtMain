<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $question_id
 * @property string $body
 * @property bool $is_correct
 * @property int $sort
 */
final class PrepChoice extends Model
{
    public $timestamps = false;

    protected $fillable = ['question_id', 'body', 'is_correct', 'sort'];

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
