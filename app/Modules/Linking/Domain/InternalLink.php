<?php

declare(strict_types=1);

namespace App\Modules\Linking\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * یک پیوند خودکار که در آخرین بازسازی گذاشته شد.
 *
 * `phrase` شکل یکسان‌شده عبارت است؛ هنگام نمایش همین عبارت دوباره در متن
 * پیدا می‌شود. `position` ترتیب آمدن در متن است.
 *
 * @property int $id
 * @property string $source_key
 * @property string $source_title
 * @property string $source_url
 * @property string $target_key
 * @property string $target_title
 * @property string $target_url
 * @property string $phrase
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class InternalLink extends Model
{
    protected $fillable = [
        'source_key',
        'source_title',
        'source_url',
        'target_key',
        'target_title',
        'target_url',
        'phrase',
        'position',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }
}
