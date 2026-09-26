<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain;

use App\Models\User;
use App\Modules\ExamPrep\Domain\Enums\PurchaseStatus;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * خرید یک بسته به دست یک کاربر؛ برای هر جفت یک ردیف. قیمت همان لحظه
 * ثبت می‌شود تا تغییر بعدی قیمت خرید گذشته را عوض نکند.
 *
 * @property int $id
 * @property string $uuid
 * @property int $exam_pack_id
 * @property int $user_id
 * @property PurchaseStatus $status
 * @property int $price_toman
 * @property PaymentSource $payment_source
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property Carbon|null $paid_at
 */
final class PackPurchase extends Model
{
    protected $table = 'exam_pack_purchases';

    protected $fillable = [
        'uuid',
        'exam_pack_id',
        'user_id',
        'status',
        'price_toman',
        'payment_source',
        'gateway_authority',
        'gateway_ref_id',
        'paid_at',
    ];

    /** @return BelongsTo<ExamPack, $this> */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(ExamPack::class, 'exam_pack_id');
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @param  Builder<self>  $query */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', PurchaseStatus::Paid->value);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    public function isPaid(): bool
    {
        return $this->status === PurchaseStatus::Paid;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PurchaseStatus::class,
            'payment_source' => PaymentSource::class,
            'paid_at' => 'datetime',
        ];
    }
}
