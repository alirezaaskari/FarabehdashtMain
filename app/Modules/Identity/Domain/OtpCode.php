<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

use App\Modules\Identity\Domain\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * رمز یک‌بارمصرف.
 *
 * کد خام هرگز ذخیره نمی‌شود — فقط هش آن.
 *
 * @property string $mobile
 * @property OtpPurpose $purpose
 * @property string $code_hash
 * @property int $attempts
 */
final class OtpCode extends Model
{
    protected $fillable = [
        'mobile',
        'purpose',
        'code_hash',
        'attempts',
        'expires_at',
        'consumed_at',
        'requested_ip',
    ];

    protected $hidden = ['code_hash'];

    /** @param  Builder<$this>  $query */
    public function scopeUsable(Builder $query): void
    {
        $query->whereNull('consumed_at')->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function hasAttemptsLeft(int $max): bool
    {
        return $this->attempts < $max;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
