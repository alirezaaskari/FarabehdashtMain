<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک تراکنش در دفتر کل — فقط افزودنی.
 *
 * خودش عدد ندارد؛ جمع ردیف‌های {@see LedgerEntry} وابسته به آن است که باید
 * صفر شود (ناوردای بخش ۱۱). `idempotency_key` یکتاست تا اجرای دوباره یک
 * عملیات مالی با همان کلید، ردیف تازه نسازد — این خودِ معیار پذیرش «اجرای
 * دوباره با همان کلید اثر دوم ندارد» است.
 *
 * برگشت یا استرداد همیشه تراکنش برگشتی تازه می‌سازد، نه ویرایش این یکی.
 *
 * @property int $id
 * @property string $uuid
 * @property string $kind
 * @property string $idempotency_key
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string|null $memo
 * @property int|null $created_by
 * @property Carbon $created_at
 */
final class LedgerTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ledger_transactions';

    protected $fillable = [
        'uuid',
        'kind',
        'idempotency_key',
        'reference_type',
        'reference_id',
        'memo',
        'created_by',
    ];

    /** @return HasMany<LedgerEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'transaction_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param  Builder<$this>  $query */
    public function scopeForReference(Builder $query, string $referenceType, string $referenceId): void
    {
        $query->where('reference_type', $referenceType)->where('reference_id', $referenceId);
    }

    /** تراکنش ثبت‌شده هرگز تغییر نمی‌کند. */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('تراکنش دفتر کل تغییرناپذیر است؛ برای اصلاح، تراکنش برگشتی ثبت کنید.');
    }

    /** تراکنش ثبت‌شده هرگز حذف نمی‌شود. */
    public function delete(): bool
    {
        throw new RuntimeException('تراکنش دفتر کل فقط افزودنی است؛ حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
