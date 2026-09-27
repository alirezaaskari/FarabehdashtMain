<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use App\Models\User;
use App\Modules\Jobs\Domain\Enums\CompanySize;
use App\Modules\Jobs\Domain\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * صفحه شرکت یک کارفرما (بخش ۲۰-۱، DEC-65).
 *
 * ستون‌های نمایشی نسخه منتشرشده‌اند؛ آخرین ویرایش فرستاده‌شده در `pending`
 * است و فقط با تأیید مدیر جای آن‌ها را می‌گیرد. آگهی فقط زیر شرکتی ثبت
 * می‌شود که یک بار تأیید شده است.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string|null $slug
 * @property string|null $name
 * @property string|null $industry
 * @property CompanySize|null $size
 * @property string|null $province
 * @property string|null $city
 * @property string|null $about
 * @property int|null $logo_id
 * @property Carbon|null $published_at
 * @property Carbon|null $hidden_at
 * @property array<string, mixed>|null $pending
 * @property ReviewStatus $status
 * @property Carbon|null $submitted_at
 * @property string|null $review_note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Collection<int, CompanyDocument> $documents
 * @property-read Collection<int, JobPosting> $postings
 */
final class Company extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'slug',
        'status',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CompanyDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(CompanyDocument::class)->oldest('id');
    }

    /** @return HasMany<JobPosting, $this> */
    public function postings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * شرکت‌هایی که همه می‌بینند: یک بار تأییدشده و پنهان‌نشده.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeListed(Builder $query): void
    {
        $query->whereNotNull('published_at')->whereNull('hidden_at');
    }

    public function isListed(): bool
    {
        return $this->published_at !== null && $this->hidden_at === null;
    }

    /** ویرایش در انتظار، یا اگر نیست نسخه منتشرشده؛ فرم ویرایش از همین پر می‌شود. */
    public function draft(): ?CompanyDraft
    {
        if ($this->pending !== null) {
            return CompanyDraft::fromArray($this->pending);
        }

        return $this->published_at === null ? null : $this->publishedDraft();
    }

    public function publishedDraft(): CompanyDraft
    {
        return new CompanyDraft(
            slug: (string) $this->slug,
            name: (string) $this->name,
            industry: (string) $this->industry,
            size: (string) $this->size?->value,
            province: (string) $this->province,
            city: (string) $this->city,
            about: (string) $this->about,
            logoId: $this->logo_id,
        );
    }

    /** @return list<string> */
    public function aboutParagraphs(): array
    {
        return array_values(array_filter(array_map(trim(...), preg_split('/\R{2,}/u', (string) $this->about) ?: [])));
    }

    protected function casts(): array
    {
        return [
            'pending' => 'array',
            'size' => CompanySize::class,
            'status' => ReviewStatus::class,
            'published_at' => 'datetime',
            'hidden_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
