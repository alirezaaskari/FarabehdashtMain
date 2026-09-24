<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * نمای انتخاب‌شده میزکار یک کاربر.
 *
 * @property int $id
 * @property int $user_id
 * @property string $view
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class WorkspacePreference extends Model
{
    protected $fillable = ['user_id', 'view'];
}
