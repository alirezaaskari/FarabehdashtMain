<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain;

use App\Modules\Consulting\Domain\Enums\ServiceKind;
use App\Modules\Consulting\Domain\Enums\ServiceStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * خدمتی که مشاور می‌فروشد: جلسه آنلاین یا بازدید حضوری.
 *
 * @property int $id
 * @property string $uuid
 * @property int $profile_id
 * @property ServiceKind $kind
 * @property string $title
 * @property string $description
 * @property int|null $duration_minutes
 * @property int $price_toman
 * @property list<string>|null $cities
 * @property ServiceStatus $status
 * @property string|null $review_note
 * @property int|null $reviewed_by
 * @property Carbon|null $submitted_at
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ConsultantProfile $profile
 */
final class ConsultingService extends Model
{
    protected $fillable = [
        'uuid',
        'profile_id',
        'kind',
        'title',
        'description',
        'duration_minutes',
        'price_toman',
        'cities',
        'status',
    ];

    /** @return BelongsTo<ConsultantProfile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultantProfile::class, 'profile_id');
    }

    /**
     * خدمت‌هایی که می‌شود خرید: منتشرشده و مال صفحه‌ای که دیده می‌شود.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOnSale(Builder $query): void
    {
        $query->where('status', ServiceStatus::Published)
            ->whereHas('profile', static fn (Builder $profile) => $profile->listed());
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    public function isOnSale(): bool
    {
        return $this->status === ServiceStatus::Published && $this->profile->isListed();
    }

    /** @return list<string> */
    public function paragraphs(): array
    {
        return array_values(array_filter(array_map(trim(...), preg_split('/\R{2,}/u', $this->description) ?: [])));
    }

    protected function casts(): array
    {
        return [
            'kind' => ServiceKind::class,
            'status' => ServiceStatus::class,
            'cities' => 'array',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
