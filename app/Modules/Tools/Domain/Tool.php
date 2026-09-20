<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * وضعیت قابل تغییرِ یک ابزار — و فقط همین.
 *
 * عنوان، خلاصه، گروه و رابطه در کد می‌مانند (`config/tools.php`). این جدول
 * فقط چیزهایی را نگه می‌دارد که مدیر بدون استقرار تازه عوضشان می‌کند:
 * فعال‌بودن، نسخه سنجاق‌شده فرمول، و تاریخ آخرین بازبینی علمی.
 *
 * نبودِ ردیف یعنی «هنوز همگام نشده»، نه «غیرفعال». ابزار تازه‌ای که در کد
 * اضافه شده باید بلافاصله کار کند، نه اینکه تا اجرای یک دستور نامرئی بماند.
 *
 * @property string $slug
 * @property bool $is_enabled
 * @property string|null $pinned_version
 * @property Carbon|null $reviewed_at
 */
final class Tool extends Model
{
    protected $table = 'tools';

    protected $primaryKey = 'slug';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['slug', 'is_enabled', 'pinned_version', 'reviewed_at'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }
}
