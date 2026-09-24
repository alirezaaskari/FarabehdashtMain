<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use App\Modules\Commerce\Domain\Enums\VersionReviewStatus;
use App\Modules\Commerce\Services\ProductVersionReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک نسخه ثبت‌شده از محصول — فقط افزودنی.
 *
 * فایل و مشخصاتش تغییرناپذیرند؛ تنها چیزی که بعد از ثبت عوض می‌شود بررسی
 * مدیر است، که از {@see ProductVersionReview} و با query builder نوشته می‌شود.
 *
 * @property int $id
 * @property int $product_id
 * @property string $version
 * @property string|null $changelog
 * @property string $file_path
 * @property int $file_size
 * @property string $checksum
 * @property VersionReviewStatus $review_status
 * @property Carbon|null $reviewed_at
 * @property string|null $review_note
 * @property Carbon $created_at
 */
final class ProductVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'version',
        'changelog',
        'file_path',
        'file_size',
        'checksum',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @param  Builder<$this>  $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('review_status', VersionReviewStatus::Approved->value);
    }

    /** @param  Builder<$this>  $query */
    public function scopePendingReview(Builder $query): void
    {
        $query->where('review_status', VersionReviewStatus::Pending->value);
    }

    public function isApproved(): bool
    {
        return $this->review_status === VersionReviewStatus::Approved;
    }

    /** نسخه ثبت‌شده هرگز تغییر نمی‌کند. */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('نسخه ثبت‌شده محصول تغییرناپذیر است؛ برای اصلاح، نسخه تازه ثبت کنید.');
    }

    /** نسخه ثبت‌شده هرگز حذف نمی‌شود. */
    public function delete(): bool
    {
        throw new RuntimeException('نسخه ثبت‌شده محصول فقط افزودنی است؛ حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'review_status' => VersionReviewStatus::class,
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
