<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain;

use App\Modules\ExamPrep\Domain\Enums\PackStatus;
use App\Modules\ExamPrep\Domain\Enums\QuestionStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک بسته آمادگی آزمون، مثل «آزمون استخدامی وزارت بهداشت». محصول پولی
 * پلتفرم است؛ بسته را فقط مدیر می‌سازد و قیمت می‌گذارد.
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $title
 * @property string $exam_name
 * @property string $description
 * @property int $price_toman
 * @property int $exam_question_count
 * @property int $exam_minutes
 * @property PackStatus $status
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ExamPack extends Model
{
    protected $fillable = [
        'uuid',
        'slug',
        'title',
        'exam_name',
        'description',
        'price_toman',
        'exam_question_count',
        'exam_minutes',
        'status',
        'published_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<PackTopic, $this> */
    public function topics(): HasMany
    {
        return $this->hasMany(PackTopic::class)->orderBy('sort')->orderBy('id');
    }

    /** @return HasMany<PrepQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(PrepQuestion::class);
    }

    /** @return HasMany<PrepQuestion, $this> */
    public function publishedQuestions(): HasMany
    {
        return $this->questions()->where('status', QuestionStatus::Published->value);
    }

    /** @param  Builder<self>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PackStatus::Published->value);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    public function isPublished(): bool
    {
        return $this->status === PackStatus::Published;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PackStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
