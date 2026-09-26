<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Domain;

use App\Modules\Webinars\Domain\Enums\RegistrationStatus;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $webinar_id
 * @property int $user_id
 * @property RegistrationStatus $status
 * @property int $price_toman
 * @property PaymentSource $payment_source
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property Carbon|null $paid_at
 * @property Carbon|null $reminded_at
 * @property Carbon $updated_at
 * @property-read Webinar $webinar
 */
final class WebinarRegistration extends Model
{
    protected $fillable = [
        'uuid',
        'webinar_id',
        'user_id',
        'status',
        'price_toman',
        'payment_source',
        'gateway_authority',
        'gateway_ref_id',
        'paid_at',
        'reminded_at',
    ];

    /** @return BelongsTo<Webinar, $this> */
    public function webinar(): BelongsTo
    {
        return $this->belongsTo(Webinar::class);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    public function isConfirmed(): bool
    {
        return $this->status === RegistrationStatus::Confirmed;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'price_toman' => 'integer',
            'payment_source' => PaymentSource::class,
            'paid_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }
}
