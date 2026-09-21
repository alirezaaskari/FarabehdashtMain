<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Domain;

use App\Models\User;
use App\Support\Ledger\EntryDirection;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * کیف پول کاربر — تنها مدل این ماژول که تغییر می‌کند.
 *
 * `cached_balance_toman` عمداً خارج از `$fillable` است تا مقداردهی گروهی
 * (`fill`, `update`, فرم‌های Filament) بی‌سروصدا نادیده گرفته شود، نه خطا
 * بدهد — به همین دلیل `Model::preventSilentlyDiscardingAttributes()` در
 * `AppServiceProvider` باید فعال باشد. تنها راه مجاز تغییر این ستون
 * {@see self::applyLedgerEntry()} است که همیشه باید داخل همان تراکنش
 * دیتابیسی صدا زده شود که ردیف {@see LedgerEntry} متناظرش را می‌نویسد
 * (مسئولیت فراخوان — سرویس دفتر کل، نه این مدل).
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $ledger_account_id
 * @property int $cached_balance_toman
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Wallet extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'ledger_account_id',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<LedgerAccount, $this> */
    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function balance(): Money
    {
        return Money::toman($this->cached_balance_toman);
    }

    /**
     * اعمال یک ردیف دفتر کل روی کش موجودی.
     *
     * اگر بدهکارکردن باعث منفی‌شدن موجودی شود، همان قاعده `Money` («مبلغ
     * منفی وجود ندارد») خودش استثنا پرتاب می‌کند — نیازی به بررسی جداگانه نیست.
     */
    public function applyLedgerEntry(EntryDirection $direction, Money $amount): void
    {
        $updated = $direction === EntryDirection::Credit
            ? $this->balance()->plus($amount)
            : $this->balance()->minus($amount);

        $this->forceFill(['cached_balance_toman' => $updated->toman])->save();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'cached_balance_toman' => 'integer',
        ];
    }
}
