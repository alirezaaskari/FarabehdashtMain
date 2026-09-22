<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Domain;

use App\Support\Ledger\EntryDirection;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک ردیف دفتر کل — فقط افزودنی.
 *
 * مبلغ (`amount_toman`) طبق قاعده {@see Money} هرگز منفی نیست؛ {@see EntryDirection}
 * می‌گوید به کدام سمت حساب می‌رود. ناوردای بخش ۱۱ در سطح تراکنش بررسی
 * می‌شود، نه این‌جا: جمع بستانکار منهای بدهکار همهٔ ردیف‌های یک تراکنش باید
 * صفر شود.
 *
 * @property int $id
 * @property int $transaction_id
 * @property int $account_id
 * @property EntryDirection $direction
 * @property int $amount_toman
 * @property Carbon $created_at
 */
final class LedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ledger_entries';

    protected $fillable = [
        'transaction_id',
        'account_id',
        'direction',
        'amount_toman',
    ];

    /** @return BelongsTo<LedgerTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'transaction_id');
    }

    /** @return BelongsTo<LedgerAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }

    public function amount(): Money
    {
        return Money::toman($this->amount_toman);
    }

    /** اثر این ردیف روی موجودی حساب — بستانکار مثبت، بدهکار منفی. */
    public function signedAmount(): int
    {
        return $this->amount_toman * $this->direction->sign();
    }

    /** ردیف ثبت‌شده هرگز تغییر نمی‌کند. */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('ردیف دفتر کل تغییرناپذیر است.');
    }

    /** ردیف ثبت‌شده هرگز حذف نمی‌شود. */
    public function delete(): bool
    {
        throw new RuntimeException('ردیف دفتر کل فقط افزودنی است؛ حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'direction' => EntryDirection::class,
            'amount_toman' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
