<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain;

use App\Models\User;
use App\Modules\Workspace\Domain\Enums\SmsDeliveryStatus;
use App\Modules\Workspace\Domain\Enums\SmsTopic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * پیامکی که برای یک اعلان در صف است یا فرستاده شده.
 *
 * هر اعلان حداکثر یک ردیف دارد. شمار ردیف‌های `Sent` امروز همان سقف روزانه
 * است، پس سقف جدول جداگانه نمی‌خواهد.
 *
 * @property int $id
 * @property int $notification_id
 * @property int $user_id
 * @property SmsTopic $topic
 * @property SmsDeliveryStatus $status
 * @property Carbon $due_at
 * @property Carbon|null $sent_at
 * @property string|null $note
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read UserNotification $notification
 * @property-read User $user
 */
final class SmsDelivery extends Model
{
    public const NOTE_DAILY_LIMIT = 'daily_limit';

    public const NOTE_STALE = 'stale';

    protected $table = 'workspace_sms_deliveries';

    protected $fillable = [
        'notification_id',
        'user_id',
        'topic',
        'status',
        'due_at',
        'sent_at',
        'note',
    ];

    /** @return BelongsTo<UserNotification, $this> */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(UserNotification::class, 'notification_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'topic' => SmsTopic::class,
            'status' => SmsDeliveryStatus::class,
            'due_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
