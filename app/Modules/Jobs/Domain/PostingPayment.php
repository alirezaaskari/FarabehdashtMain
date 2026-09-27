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
 * یک دوره انتشار آگهی (DEC-63): پولی، یا «اولین آگهی رایگان» با قیمت صفر.
 * قیمت و مدت همان لحظه ثبت می‌شود و تغییر بعدی تنظیم مدیر رویش اثر ندارد.
 *
 * @property int $id
 * @property string $uuid
 * @property int $posting_id
 * @property int $price_toman
 * @property int $days
 * @property bool $is_free
 * @property PaymentStatus $status
 * @property PaymentSource|null $payment_source
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property-read JobPosting $posting
 */
final class PostingPayment extends Model
{
    protected $table = 'job_posting_payments';

    protected $fillable = [
        'uuid',
        'posting_id',
        'price_toman',
        'days',
        'is_free',
        'status',
    ];

    /** @return BelongsTo<JobPosting, $this> */
    public function posting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'posting_id');
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
            'is_free' => 'boolean',
            'status' => PaymentStatus::class,
            'payment_source' => PaymentSource::class,
            'paid_at' => 'datetime',
        ];
    }
}
