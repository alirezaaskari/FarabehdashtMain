<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک نرخ کمیسیون با تاریخ اثر — فقط افزودنی.
 *
 * @property int $id
 * @property string $flow
 * @property int $rate_bp
 * @property Carbon $effective_from
 * @property int|null $created_by
 * @property Carbon $created_at
 */
final class CommissionRate extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'flow',
        'rate_bp',
        'effective_from',
        'created_by',
    ];

    /** @param  Builder<$this>  $query */
    public function scopeForFlow(Builder $query, string $flow): void
    {
        $query->where('flow', $flow);
    }

    /** نرخ ثبت‌شده هرگز تغییر نمی‌کند؛ برای تغییر نرخ، ردیف تازه با تاریخ اثر بعدی ثبت کنید. */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('نرخ کمیسیون ثبت‌شده تغییرناپذیر است.');
    }

    public function delete(): bool
    {
        throw new RuntimeException('نرخ کمیسیون فقط افزودنی است؛ حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rate_bp' => 'integer',
            'effective_from' => 'date',
            'created_at' => 'datetime',
        ];
    }
}
