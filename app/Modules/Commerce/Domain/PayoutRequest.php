<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use App\Models\User;
use App\Modules\Commerce\Domain\Enums\PayoutStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * یک درخواست تسویه. اثر مالی فقط هنگام «واریز شد» در دفتر کل ثبت می‌شود؛
 * درخواست باز به خودی خود پولی جابه‌جا نمی‌کند.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $amount_toman
 * @property PayoutStatus $status
 * @property string $sheba
 * @property string $holder_name
 * @property int|null $decided_by
 * @property Carbon|null $decided_at
 * @property string|null $bank_reference
 * @property string|null $note
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class PayoutRequest extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'amount_toman',
        'status',
        'sheba',
        'holder_name',
        'decided_by',
        'decided_at',
        'bank_reference',
        'note',
    ];

    protected $hidden = ['sheba'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', PayoutStatus::Requested->value);
    }

    public function amount(): Money
    {
        return Money::toman($this->amount_toman);
    }

    public function sheba(): Sheba
    {
        return Sheba::fromInput($this->sheba);
    }

    public function isOpen(): bool
    {
        return $this->status === PayoutStatus::Requested;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PayoutStatus::class,
            'sheba' => 'encrypted',
            'decided_at' => 'datetime',
        ];
    }
}
