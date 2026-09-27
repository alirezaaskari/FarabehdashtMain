<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain;

use App\Models\User;
use App\Modules\Consulting\Domain\Enums\ProfileReviewStatus;
use App\Modules\Consulting\Domain\Enums\ProviderKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * صفحه عمومی یک مشاور یا آزمایشگاه (بخش ۱۹-۵، DEC-58).
 *
 * ستون‌های نمایشی نسخه منتشرشده‌اند؛ آخرین ویرایش فرستاده‌شده در `pending`
 * است و فقط با تأیید مدیر جای آن‌ها را می‌گیرد.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property ProviderKind $kind
 * @property list<string>|null $offerings
 * @property string|null $slug
 * @property string|null $display_name
 * @property string|null $headline
 * @property string|null $bio
 * @property string|null $province
 * @property string|null $city
 * @property int|null $photo_id
 * @property string|null $experience
 * @property string|null $education
 * @property Carbon|null $published_at
 * @property Carbon|null $hidden_at
 * @property array<string, mixed>|null $pending
 * @property ProfileReviewStatus $status
 * @property Carbon|null $submitted_at
 * @property string|null $review_note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Collection<int, ConsultantDocument> $documents
 * @property-read Collection<int, ConsultingService> $services
 */
final class ConsultantProfile extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'kind',
        'slug',
        'status',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ConsultantDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ConsultantDocument::class, 'profile_id')->oldest('id');
    }

    /** @return HasMany<ConsultingService, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(ConsultingService::class, 'profile_id');
    }

    /**
     * صفحه‌هایی که همه می‌بینند: یک بار تأییدشده و پنهان‌نشده.
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
    public function draft(): ?ProfileDraft
    {
        if ($this->pending !== null) {
            return ProfileDraft::fromArray($this->pending);
        }

        return $this->published_at === null ? null : $this->publishedDraft([]);
    }

    /** @param  list<int>  $domainIds */
    public function publishedDraft(array $domainIds): ProfileDraft
    {
        return new ProfileDraft(
            slug: (string) $this->slug,
            displayName: (string) $this->display_name,
            headline: (string) $this->headline,
            bio: (string) $this->bio,
            province: (string) $this->province,
            city: (string) $this->city,
            experience: $this->experience,
            education: $this->education,
            domainIds: $domainIds,
            photoId: $this->photo_id,
            offerings: $this->offerings ?? [],
        );
    }

    public function isLaboratory(): bool
    {
        return $this->kind === ProviderKind::Laboratory;
    }

    /** نشانی صفحه عمومی، بسته به نوع. */
    public function publicUrl(): string
    {
        return route($this->kind->route(), $this->slug);
    }

    /** @return list<string> */
    public function bioParagraphs(): array
    {
        return array_values(array_filter(array_map(trim(...), preg_split('/\R{2,}/u', (string) $this->bio) ?: [])));
    }

    protected function casts(): array
    {
        return [
            'pending' => 'array',
            'kind' => ProviderKind::class,
            'offerings' => 'array',
            'status' => ProfileReviewStatus::class,
            'published_at' => 'datetime',
            'hidden_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
