<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use App\Modules\Jobs\Domain\Enums\PaymentStatus;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * بسته اعتبار درخواست تماس بانک رزومه (DEC-72). تعداد و قیمت همان لحظه
 * خرید ثبت می‌شود و تغییر بعدی تنظیم مدیر رویش اثر ندارد.
 *
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property int $credits
 * @property int $price_toman
 * @property PaymentStatus $status
 * @property PaymentSource|null $payment_source
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property-read Company $company
 */
final class BankPackage extends Model
{
    protected $table = 'resume_bank_packages';

    protected $fillable = [
        'uuid',
        'company_id',
        'credits',
        'price_toman',
        'status',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @param  Builder<$this>  $query */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', PaymentStatus::Paid);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'status' => PaymentStatus::class,
            'payment_source' => PaymentSource::class,
            'paid_at' => 'datetime',
        ];
    }
}
