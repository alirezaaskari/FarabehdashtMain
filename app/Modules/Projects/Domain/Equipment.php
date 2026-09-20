<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

use App\Models\User;
use App\Modules\Projects\Domain\Enums\CalibrationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * یک تجهیز در دفترچه کاربر.
 *
 * ⚠️ ثبت تجهیز در فرابهداشت جایگزین گواهی کالیبراسیون رسمی نیست و صحت
 * داده‌های واردشده بر عهده کاربر است. این جدول یک دفترچه یادداشت است، نه
 * یک مرجع صدور گواهی.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $manufacturer
 * @property string|null $model
 * @property string|null $serial_number
 * @property string|null $accuracy_class
 * @property Carbon|null $calibrated_on
 * @property Carbon|null $calibration_valid_until
 * @property string|null $calibration_reference
 * @property string|null $notes
 */
final class Equipment extends Model
{
    protected $table = 'equipment';

    protected $fillable = [
        'user_id',
        'name',
        'manufacturer',
        'model',
        'serial_number',
        'accuracy_class',
        'calibrated_on',
        'calibration_valid_until',
        'calibration_reference',
        'notes',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<$this>  $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * وضعیت کالیبراسیون، نسبت به امروز.
     *
     * آستانه «نزدیک انقضا» از پیکربندی می‌آید تا کارشناسی که چرخه پایش
     * فصلی دارد بتواند زودتر خبردار شود.
     */
    public function calibrationStatus(?int $warningDays = null): CalibrationStatus
    {
        if ($this->calibration_valid_until === null) {
            return CalibrationStatus::NotRecorded;
        }

        if ($this->calibration_valid_until->isPast()) {
            return CalibrationStatus::Expired;
        }

        $days = $warningDays ?? (int) config('projects.calibration_warning_days', 30);

        return $this->calibration_valid_until->isBefore(now()->addDays($days))
            ? CalibrationStatus::ExpiringSoon
            : CalibrationStatus::Valid;
    }

    /**
     * شناسه‌ای که در گزارش می‌نشیند: مدل و سریال، یا هر تکه‌ای که موجود است.
     */
    public function identification(): string
    {
        return implode(' · ', array_filter([
            $this->name,
            $this->model,
            $this->serial_number === null ? null : 'سریال '.$this->serial_number,
        ]));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'calibrated_on' => 'date',
            'calibration_valid_until' => 'date',
        ];
    }
}
