<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain;

use App\Models\User;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * یک گزارش — پیش‌نویس یا صادرشده.
 *
 * پس از صدور، گزارش فقط از `snapshot` خوانده می‌شود، نه از منبعش: پروژه‌ای
 * که فردا ویرایش شود نباید سندی را که دیروز امضا و تحویل شده عوض کند.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property ReportStatus $status
 * @property string $source_key
 * @property list<string> $source_references
 * @property string|null $title
 * @property string|null $client_name
 * @property string|null $site
 * @property string|null $measured_on
 * @property string|null $author_name
 * @property string|null $findings
 * @property string|null $recommendations
 * @property bool $include_equipment
 * @property bool $include_method
 * @property int $revision
 * @property int|null $supersedes_id
 * @property int|null $superseded_by_id
 * @property string|null $tracking_code
 * @property array<string, mixed>|null $snapshot
 * @property string|null $pdf_path
 * @property string|null $pdf_sha256
 * @property Carbon|null $issued_at
 * @property Carbon|null $revoked_at
 * @property string|null $revoke_reason
 * @property int|null $revoked_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Report extends Model
{
    /** پیش‌فرض‌های جدول، تا مدل تازه‌ساخته پیش از بازخوانی هم کامل باشد. */
    protected $attributes = [
        'status' => 'draft',
        'include_equipment' => true,
        'include_method' => true,
        'revision' => 1,
    ];

    protected $fillable = [
        'uuid',
        'user_id',
        'status',
        'source_key',
        'source_references',
        'title',
        'client_name',
        'site',
        'measured_on',
        'author_name',
        'findings',
        'recommendations',
        'include_equipment',
        'include_method',
        'revision',
        'supersedes_id',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<self, $this> */
    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    /** @return BelongsTo<self, $this> */
    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    /** @param  Builder<$this>  $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /** @param  Builder<$this>  $query */
    public function scopeIssued(Builder $query): void
    {
        $query->whereNotNull('tracking_code');
    }

    public function isDraft(): bool
    {
        return $this->status === ReportStatus::Draft;
    }

    /** سند منجمد گزارش صادرشده؛ پیش‌نویس سند منجمد ندارد. */
    public function document(): ?ReportDocument
    {
        return $this->snapshot === null ? null : ReportDocument::fromArray($this->snapshot);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'source_references' => 'array',
            'snapshot' => 'array',
            'include_equipment' => 'boolean',
            'include_method' => 'boolean',
            'revision' => 'integer',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
