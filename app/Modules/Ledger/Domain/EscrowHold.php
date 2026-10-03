<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Domain;

use App\Support\Escrow\EscrowHold as EscrowHoldData;
use App\Support\Escrow\EscrowStatus;
use App\Support\Ledger\AccountType;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * یک امانت خدمت یا مرحله پروژه. فقط `EscrowService` آن را می‌نویسد.
 *
 * @property int $id
 * @property string $uuid
 * @property string $key
 * @property int $payer_user_id
 * @property int $payee_user_id
 * @property int $amount_toman
 * @property int $commission_toman
 * @property int $refunded_toman
 * @property AccountType $account
 * @property PaymentSource $payment_source
 * @property EscrowStatus $status
 * @property string $reference_type
 * @property string $reference_id
 * @property Carbon $held_at
 * @property Carbon|null $closed_at
 * @property int|null $closed_by
 * @property string|null $close_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class EscrowHold extends Model
{
    protected $fillable = [
        'uuid',
        'key',
        'payer_user_id',
        'payee_user_id',
        'amount_toman',
        'commission_toman',
        'refunded_toman',
        'account',
        'payment_source',
        'status',
        'reference_type',
        'reference_id',
        'held_at',
        'closed_at',
        'closed_by',
        'close_reason',
    ];

    protected function casts(): array
    {
        return [
            'account' => AccountType::class,
            'payment_source' => PaymentSource::class,
            'status' => EscrowStatus::class,
            'held_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function amount(): Money
    {
        return Money::toman($this->amount_toman);
    }

    public function commission(): Money
    {
        return Money::toman($this->commission_toman);
    }

    public function toData(): EscrowHoldData
    {
        return new EscrowHoldData(
            uuid: $this->uuid,
            key: $this->key,
            payerUserId: $this->payer_user_id,
            payeeUserId: $this->payee_user_id,
            amount: $this->amount(),
            commission: $this->commission(),
            status: $this->status,
            refunded: Money::toman($this->refunded_toman),
            heldAt: $this->held_at->toImmutable(),
            closedAt: $this->closed_at?->toImmutable(),
            account: $this->account,
        );
    }
}
