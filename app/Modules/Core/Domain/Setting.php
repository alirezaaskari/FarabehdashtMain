<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * یک تنظیم قابل تغییر توسط مدیر.
 *
 * مقدار JSON است تا نوع اصلی (bool، int، آرایه) حفظ شود؛ تنظیمی که «true» و
 * «۱» و «بله» را قاطی کند، دیر یا زود یک کلید درآمدی را اشتباه خاموش می‌کند.
 *
 * خواندن همیشه از `SettingsRepository` است، نه مستقیم از این مدل.
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property string $group
 * @property string|null $description
 */
final class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'description'];

    /** @param  Builder<$this>  $query */
    public function scopeInGroup(Builder $query, string $group): void
    {
        $query->where('group', $group);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}
