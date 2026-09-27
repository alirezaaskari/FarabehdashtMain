<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use App\Models\User;
use App\Modules\Jobs\Domain\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * درخواست رایگان کارجو برای یک آگهی (۲۰-۲).
 *
 * شماره و ایمیل کارجو فقط وقتی به کارفرما نشان داده می‌شود که `share_contact`
 * روشن باشد (DEC-68)؛ کارجو هر زمان خاموشش می‌کند. هر نمایش در
 * {@see ResumeAccessLog} ثبت می‌شود.
 *
 * @property int $id
 * @property string $uuid
 * @property int $posting_id
 * @property int $user_id
 * @property string $cover_note
 * @property string|null $resume_path
 * @property string|null $resume_name
 * @property int $resume_size_bytes
 * @property bool $share_contact
 * @property ApplicationStatus $status
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read JobPosting $posting
 * @property-read User $user
 * @property-read Collection<int, ApplicationMessage> $messages
 */
final class JobApplication extends Model
{
    protected $fillable = [
        'uuid',
        'posting_id',
        'user_id',
        'cover_note',
        'resume_path',
        'resume_name',
        'resume_size_bytes',
        'share_contact',
        'status',
    ];

    /** @return BelongsTo<JobPosting, $this> */
    public function posting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'posting_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ApplicationMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ApplicationMessage::class, 'application_id')->oldest('id');
    }

    /** صاحب صفحه شرکتی که آگهی زیر آن است. */
    public function employerId(): int
    {
        return $this->posting->company->user_id;
    }

    public function isParty(int $userId): bool
    {
        return $userId === $this->user_id || $userId === $this->employerId();
    }

    /** @return list<string> */
    public function coverParagraphs(): array
    {
        return array_values(array_filter(array_map(trim(...), preg_split('/\R{2,}/u', $this->cover_note) ?: [])));
    }

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'share_contact' => 'boolean',
            'status_changed_at' => 'datetime',
        ];
    }
}
