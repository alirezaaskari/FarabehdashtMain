<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * پروژه‌ای که کارفرما در بازار تعریف کرده (بخش ۲۱-۲).
 *
 * پیش از انتشار تأیید مدیر می‌خواهد (DEC-76). نام کارفرما پیش‌فرض پنهان است
 * (DEC-84) و پروژه خصوصی در فهرست و نقشه سایت نمی‌آید (DEC-90).
 *
 * @property int $id
 * @property string $uuid
 * @property int $client_user_id
 * @property string $title
 * @property string $service
 * @property string|null $province
 * @property string|null $city
 * @property bool $remote
 * @property int $budget_min_toman
 * @property int $budget_max_toman
 * @property Carbon|null $wanted_by
 * @property string $description
 * @property string|null $client_name
 * @property bool $show_client_name
 * @property bool $is_private
 * @property ProjectStatus $status
 * @property string|null $review_note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $published_at
 * @property Carbon|null $bids_close_at
 * @property Carbon|null $closed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, ProjectFile> $files
 * @property-read Collection<int, MarketBid> $bids
 * @property-read Collection<int, MarketInvite> $invites
 * @property-read MarketContract|null $contract
 */
final class MarketProject extends Model
{
    protected $fillable = [
        'uuid',
        'client_user_id',
        'title',
        'service',
        'province',
        'city',
        'remote',
        'budget_min_toman',
        'budget_max_toman',
        'wanted_by',
        'description',
        'client_name',
        'show_client_name',
        'is_private',
        'status',
        'submitted_at',
    ];

    /** @return HasMany<ProjectFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class, 'project_id');
    }

    /** @return HasMany<MarketBid, $this> */
    public function bids(): HasMany
    {
        return $this->hasMany(MarketBid::class, 'project_id');
    }

    /**
     * قرارداد جاری یا تمام‌شده؛ قرارداد بی‌اثرشده حساب نیست.
     *
     * @return HasOne<MarketContract, $this>
     */
    public function contract(): HasOne
    {
        return $this->hasOne(MarketContract::class, 'project_id')->where('status', '!=', ContractStatus::Lapsed)->latest('id');
    }

    /** @return HasMany<MarketInvite, $this> */
    public function invites(): HasMany
    {
        return $this->hasMany(MarketInvite::class, 'project_id');
    }

    public function isInvited(int $userId): bool
    {
        return $this->invites()->where('provider_user_id', $userId)->exists();
    }

    /**
     * پروژه‌هایی که در فهرست عمومی می‌آیند: باز، عمومی و پیش از پایان مهلت پیشنهاد.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeListed(Builder $query, ?Carbon $now = null): void
    {
        $query->where('status', ProjectStatus::Open)
            ->where('is_private', false)
            ->where('bids_close_at', '>', $now ?? Carbon::now());
    }

    public function acceptsBids(?Carbon $now = null): bool
    {
        return $this->status === ProjectStatus::Open
            && $this->bids_close_at !== null
            && $this->bids_close_at->greaterThan($now ?? Carbon::now());
    }

    public function budgetMin(): Money
    {
        return Money::toman($this->budget_min_toman);
    }

    public function budgetMax(): Money
    {
        return Money::toman($this->budget_max_toman);
    }

    /** @return list<string> */
    public function descriptionParagraphs(): array
    {
        return array_values(array_filter(array_map(trim(...), preg_split('/\R{2,}/u', $this->description) ?: [])));
    }

    protected function casts(): array
    {
        return [
            'remote' => 'boolean',
            'show_client_name' => 'boolean',
            'is_private' => 'boolean',
            'status' => ProjectStatus::class,
            'wanted_by' => 'date',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
            'bids_close_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
