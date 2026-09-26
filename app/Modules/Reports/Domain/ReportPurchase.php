<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain;

use App\Models\User;
use App\Modules\Reports\Domain\Enums\ReportPurchaseStatus;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * خرید تکی صدور یک گزارش (DEC-44) — قیمت Snapshot لحظه خرید.
 *
 * @property int $id
 * @property string $uuid
 * @property int $report_id
 * @property int $user_id
 * @property ReportPurchaseStatus $status
 * @property int $price_toman
 * @property PaymentSource $payment_source
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Report $report
 */
final class ReportPurchase extends Model
{
    protected $fillable = [
        'uuid',
        'report_id',
        'user_id',
        'status',
        'price_toman',
        'payment_source',
        'gateway_authority',
        'gateway_ref_id',
        'paid_at',
    ];

    /** @return BelongsTo<Report, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @param  Builder<$this>  $query */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', ReportPurchaseStatus::Paid);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    public function isPaid(): bool
    {
        return $this->status === ReportPurchaseStatus::Paid;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ReportPurchaseStatus::class,
            'payment_source' => PaymentSource::class,
            'paid_at' => 'datetime',
        ];
    }
}
