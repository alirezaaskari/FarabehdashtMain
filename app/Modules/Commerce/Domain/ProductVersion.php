<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک نسخه ثبت‌شده از محصول — فقط افزودنی.
 *
 * @property int $id
 * @property int $product_id
 * @property string $version
 * @property string|null $changelog
 * @property string $file_path
 * @property int $file_size
 * @property string $checksum
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
            'created_at' => 'datetime',
        ];
    }
}
