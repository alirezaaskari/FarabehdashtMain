<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * درخواست تماس با یک آزمایشگاه از صفحه‌اش (بخش ۱۹-۵، DEC-58).
 *
 * @property int $id
 * @property string $uuid
 * @property int $profile_id
 * @property int $user_id
 * @property string|null $service
 * @property string|null $city
 * @property string $message
 * @property bool $share_mobile
 * @property string|null $reply
 * @property Carbon|null $replied_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ConsultantProfile $profile
 * @property-read User $user
 */
final class DirectoryContact extends Model
{
    protected $fillable = [
        'uuid',
        'profile_id',
        'user_id',
        'service',
        'city',
        'message',
        'share_mobile',
    ];

    /** @return BelongsTo<ConsultantProfile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultantProfile::class, 'profile_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'share_mobile' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }
}
