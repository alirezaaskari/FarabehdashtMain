<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_id
 * @property string $text
 * @property int $position
 * @property Carbon|null $approved_at
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

    /**
     * دیده‌شدنی برای دانشجو: یا با انتشار دوره تأیید شده، یا مدیر افزوده‌اش را
     * به دوره منتشرشده جدا تأیید کرده.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->whereNotNull('approved_at');
    }

    /** @param  Builder<$this>  $query */
    public function scopePendingApproval(Builder $query): void
    {
        $query->whereNull('approved_at');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'approved_at' => 'datetime',
        ];
    }
}
