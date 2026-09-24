<?php

declare(strict_types=1);

namespace App\Modules\Linking\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * عکس فوری یک مقصد پیوند در آخرین بازسازی — پایه گزارش صفحه‌های یتیم.
 *
 * @property int $id
 * @property string $key
 * @property string $title
 * @property string $url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class LinkTargetRecord extends Model
{
    protected $table = 'link_targets';

    protected $fillable = ['key', 'title', 'url'];
}
