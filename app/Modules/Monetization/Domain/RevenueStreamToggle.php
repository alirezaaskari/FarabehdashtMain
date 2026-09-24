<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain;

use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Enums\ShutdownPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * وضعیت روشن/خاموش یک جریان درآمدی.
 *
 * ردیف فقط وقتی ساخته می‌شود که مدیر کلیدی را عوض کند؛ تا آن‌وقت پیش‌فرض
 * `config/monetization.php` حرف آخر را می‌زند. نتیجه: نصب تازه بدون هیچ
 * seedای درست کار می‌کند و جدول خالی یعنی «هیچ‌کس هنوز چیزی را عوض نکرده».
 *
 * @property int $id
 * @property RevenueStream $stream
 * @property bool $is_enabled
 * @property ShutdownPolicy $shutdown_policy
 * @property Carbon|null $changed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class RevenueStreamToggle extends Model
{
    protected $table = 'revenue_streams';

    protected $fillable = [
        'stream',
        'is_enabled',
        'shutdown_policy',
        'changed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'stream' => RevenueStream::class,
            'is_enabled' => 'boolean',
            'shutdown_policy' => ShutdownPolicy::class,
            'changed_at' => 'datetime',
        ];
    }
}
