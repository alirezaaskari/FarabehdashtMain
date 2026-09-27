<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use App\Models\User;
use App\Modules\Jobs\Domain\Enums\BankRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * درخواست تماس یک کارفرما با یک عضو بانک رزومه (۲۰-۵). نام و راه تماس
 * کارجو فقط پس از «پذیرفته» به کارفرما می‌رسد.
 *
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property int $employer_id
 * @property int $jobseeker_id
 * @property string|null $note
 * @property BankRequestStatus $status
 * @property bool $charged
 * @property Carbon $expires_at
 * @property Carbon|null $answered_at
 * @property Carbon $created_at
 * @property-read Company $company
 * @property-read User $jobseeker
 */
final class BankRequest extends Model
{
    protected $table = 'resume_bank_requests';

    protected $fillable = [
        'uuid',
        'company_id',
        'employer_id',
        'jobseeker_id',
        'note',
        'status',
        'charged',
        'expires_at',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function jobseeker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jobseeker_id');
    }

    public function isAnswerable(?Carbon $now = null): bool
    {
        return $this->status === BankRequestStatus::Pending && $this->expires_at->isAfter($now ?? Carbon::now());
    }

    protected function casts(): array
    {
        return [
            'status' => BankRequestStatus::class,
            'charged' => 'boolean',
            'expires_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }
}
