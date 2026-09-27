<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use App\Modules\Jobs\Domain\Enums\EmploymentType;
use App\Modules\Jobs\Domain\Enums\PostingState;
use App\Modules\Jobs\Domain\Enums\ReviewStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * آگهی شغلی (بخش ۲۰-۱).
 *
 * دو محور جدا دارد: وضعیت بازبینی آخرین نسخه فرستاده‌شده (`status`) و جایگاه
 * انتشار که از ستون‌های زمانی حساب می‌شود ({@see PostingState}). ویرایش آگهی
 * منتشرشده در `pending` تا تأیید مدیر می‌ماند و نسخه قبلی دیده می‌شود.
 *
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property string|null $title
 * @property string|null $province
 * @property string|null $city
 * @property EmploymentType|null $employment_type
 * @property int|null $min_experience_years
 * @property int|null $salary_min_toman
 * @property int|null $salary_max_toman
 * @property string|null $description
 * @property Carbon|null $approved_at
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $closed_at
 * @property array<string, mixed>|null $pending
 * @property ReviewStatus $status
 * @property Carbon|null $submitted_at
 * @property string|null $review_note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Company $company
 * @property-read Collection<int, PostingPayment> $payments
 * @property-read Collection<int, JobApplication> $applications
 */
final class JobPosting extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'status',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return HasMany<PostingPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(PostingPayment::class, 'posting_id');
    }

    /** @return HasMany<JobApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'posting_id');
    }

    /**
     * آگهی‌هایی که همه می‌بینند و در فهرست می‌آیند: منتشرشده، پیش از پایان
     * اعتبار، بسته‌نشده و زیر شرکتی که پنهان نیست.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeLive(Builder $query, ?Carbon $now = null): void
    {
        $query->whereNotNull('published_at')
            ->where('expires_at', '>', $now ?? Carbon::now())
            ->whereNull('closed_at')
            ->whereHas('company', static fn (Builder $company) => $company->whereNotNull('published_at')->whereNull('hidden_at'));
    }

    public function state(?Carbon $now = null): PostingState
    {
        $now ??= Carbon::now();

        return match (true) {
            $this->closed_at !== null => PostingState::Closed,
            $this->published_at === null => $this->approved_at === null ? PostingState::Unpublished : PostingState::AwaitingPayment,
            $this->expires_at === null || $this->expires_at->lessThanOrEqualTo($now) => PostingState::Expired,
            default => PostingState::Live,
        };
    }

    public function isLive(?Carbon $now = null): bool
    {
        return $this->state($now) === PostingState::Live && $this->company->isListed();
    }

    /** زمانی که آگهی از دید عموم بیرون رفت: بستن کارفرما یا پایان اعتبار. */
    public function endedAt(): ?Carbon
    {
        return $this->closed_at ?? ($this->published_at === null ? null : $this->expires_at);
    }

    /** ویرایش در انتظار، یا اگر نیست نسخه منتشرشده؛ فرم ویرایش از همین پر می‌شود. */
    public function draft(): ?PostingDraft
    {
        return $this->pending !== null ? PostingDraft::fromArray($this->pending) : null;
    }

    /** @param  list<int>  $skillIds */
    public function publishedDraft(array $skillIds): PostingDraft
    {
        return new PostingDraft(
            title: (string) $this->title,
            province: (string) $this->province,
            city: (string) $this->city,
            employmentType: $this->employment_type ?? EmploymentType::FullTime,
            minExperienceYears: (int) $this->min_experience_years,
            salaryMinToman: $this->salary_min_toman,
            salaryMaxToman: $this->salary_max_toman,
            description: (string) $this->description,
            skillIds: $skillIds,
        );
    }

    public function hasSalary(): bool
    {
        return $this->salary_min_toman !== null || $this->salary_max_toman !== null;
    }

    public function salaryMin(): ?Money
    {
        return $this->salary_min_toman === null ? null : Money::toman($this->salary_min_toman);
    }

    public function salaryMax(): ?Money
    {
        return $this->salary_max_toman === null ? null : Money::toman($this->salary_max_toman);
    }

    /** @return list<string> */
    public function descriptionParagraphs(): array
    {
        return array_values(array_filter(array_map(trim(...), preg_split('/\R{2,}/u', (string) $this->description) ?: [])));
    }

    protected function casts(): array
    {
        return [
            'pending' => 'array',
            'employment_type' => EmploymentType::class,
            'status' => ReviewStatus::class,
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
