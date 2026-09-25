<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Domain;

use App\Contracts\Revisable;
use App\Models\User;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک محتوای دانشنامه.
 *
 * مدل فقط داده و رابطه دارد. قاعده‌های سرمقاله‌ای — «بدون بازبین منتشر
 * نمی‌شود»، «موعد بازبینی از نوع محتوا می‌آید» — در اکشن و سرویس‌اند، چون
 * تصمیم‌اند و نه ویژگی ردیف (قاعده ۴).
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property ArticleType $type
 * @property ArticleStatus $status
 * @property string|null $review_note
 * @property string $title
 * @property string $summary
 * @property int|null $author_id
 * @property int|null $reviewer_id
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $review_due_at
 * @property Carbon|null $published_at
 * @property int $view_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Article extends Model implements Revisable
{
    protected $fillable = [
        'uuid',
        'slug',
        'type',
        'status',
        'title',
        'summary',
        'author_id',
        'reviewer_id',
        'reviewed_at',
        'review_due_at',
        'published_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return HasMany<ArticleSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(ArticleSection::class)->orderBy('position');
    }

    /** @return HasMany<ArticleReference, $this> */
    public function references(): HasMany
    {
        return $this->hasMany(ArticleReference::class)->orderBy('position');
    }

    /** پیوندهای دستی این محتوا به محتواهای دیگر — جهت‌دار. */
    /** @return BelongsToMany<self, $this> */
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'article_links', 'article_id', 'linked_article_id')
            ->withPivot('position')
            ->orderBy('article_links.position');
    }

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published->value);
    }

    /** @param  Builder<$this>  $query */
    public function scopeOfType(Builder $query, ArticleType $type): void
    {
        $query->where('type', $type->value);
    }

    /**
     * محتوا بازبینی‌شده است: هم بازبین دارد، هم تاریخ بازبینی.
     *
     * یکی بدون دیگری بی‌معناست — نامی بدون تاریخ نمی‌گوید کِی، و تاریخی بدون
     * نام نمی‌گوید چه کسی پایش ایستاده است.
     */
    public function isReviewed(): bool
    {
        return $this->reviewer_id !== null && $this->reviewed_at !== null;
    }

    /** @return array<string, mixed> */
    public function revisionSnapshot(): array
    {
        return [
            'title' => $this->title,
            'summary' => $this->summary,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'reviewer_id' => $this->reviewer_id,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'sections' => $this->sections->map(static fn (ArticleSection $section): array => [
                'heading' => $section->heading,
                'body' => $section->body,
                'note' => $section->note,
                'tool_slug' => $section->tool_slug,
            ])->all(),
            'references' => $this->references->map(static fn (ArticleReference $reference): array => [
                'title' => $reference->title,
                'publisher' => $reference->publisher,
                'edition' => $reference->edition,
                'year' => $reference->year,
            ])->all(),
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ArticleType::class,
            'status' => ArticleStatus::class,
            'reviewed_at' => 'datetime',
            'review_due_at' => 'datetime',
            'published_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }
}
