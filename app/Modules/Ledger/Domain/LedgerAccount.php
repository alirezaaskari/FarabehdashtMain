<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Domain;

use App\Support\Ledger\AccountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک حساب در دفتر کل — فقط افزودنی.
 *
 * حساب بعد از ساخته‌شدن هرگز تغییر نمی‌کند، حتی نوعش (توضیح در مهاجرت). اگر
 * حساب اشتباه ساخته شود، حساب اصلاح‌شده تازه ساخته می‌شود، نه ویرایش این یکی.
 *
 * @property int $id
 * @property string $uuid
 * @property AccountType $type
 * @property string|null $owner_type
 * @property int|null $owner_id
 * @property string $currency
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class LedgerAccount extends Model
{
    protected $table = 'ledger_accounts';

    protected $fillable = [
        'uuid',
        'type',
        'owner_type',
        'owner_id',
        'currency',
    ];

    /** @return HasMany<LedgerEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'account_id');
    }

    /** @param  Builder<$this>  $query */
    public function scopeOfType(Builder $query, AccountType $type): void
    {
        $query->where('type', $type->value);
    }

    /** @param  Builder<$this>  $query */
    public function scopeOwnedBy(Builder $query, string $ownerType, int $ownerId): void
    {
        $query->where('owner_type', $ownerType)->where('owner_id', $ownerId);
    }

    /** حساب ساخته‌شده هرگز تغییر نمی‌کند. */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('حساب دفتر کل تغییرناپذیر است؛ برای اصلاح، حساب تازه بسازید.');
    }

    /** حساب ساخته‌شده هرگز حذف نمی‌شود. */
    public function delete(): bool
    {
        throw new RuntimeException('حساب دفتر کل فقط افزودنی است؛ حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'owner_id' => 'integer',
        ];
    }
}
