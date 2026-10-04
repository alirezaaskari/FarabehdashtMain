<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * یک اخطار: پیامی که مدیر رد کرد. اخطارهای پاک‌نشده به سقف که برسند، پیشنهاد و
 * تعریف پروژه بسته می‌شود تا مدیر دوباره باز کند (DEC-80).
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $message_id
 * @property Carbon|null $cleared_at
 * @property int|null $cleared_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class MarketStrike extends Model
{
    protected $fillable = ['user_id', 'message_id'];

    protected function casts(): array
    {
        return ['cleared_at' => 'datetime'];
    }
}
