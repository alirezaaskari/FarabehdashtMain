<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * درخواست «خبرم کن وقتی رفع شد» یک کاربر برای یک رویداد.
 *
 * @property int $id
 * @property int $incident_id
 * @property int $user_id
 * @property Carbon $created_at
 */
final class IncidentSubscription extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['incident_id', 'user_id'];
}
