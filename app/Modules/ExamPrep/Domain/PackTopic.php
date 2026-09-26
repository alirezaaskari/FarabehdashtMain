<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * موضوع یک بسته، مثل «سم‌شناسی» یا «ارگونومی». کارنامه ضعف را بر همین
 * پایه نشان می‌دهد.
 *
 * @property int $id
 * @property int $exam_pack_id
 * @property string $title
 * @property int $sort
 */
final class PackTopic extends Model
{
    protected $table = 'exam_pack_topics';

    protected $fillable = ['exam_pack_id', 'title', 'sort'];

    /** @return BelongsTo<ExamPack, $this> */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(ExamPack::class, 'exam_pack_id');
    }

    /** @return HasMany<PrepQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(PrepQuestion::class, 'topic_id');
    }
}
