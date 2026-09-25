<?php

declare(strict_types=1);

namespace App\Modules\Projects\Actions;

use App\Modules\Projects\Domain\Equipment;
use App\Modules\Projects\Events\CalibrationDueSoon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

/**
 * یادآور نزدیک‌شدن پایان کالیبراسیون، یک‌بار برای هر تاریخ اعتبار.
 *
 * آستانه همان «نزدیک انقضا»ی دفترچه تجهیزات است
 * (`projects.calibration_warning_days`) تا اعلان و نشان صفحه یک حرف بزنند.
 * تجهیزی که پیش از ثبت منقضی شده یادآور نمی‌گیرد؛ نشان «منقضی» خودش پیداست.
 */
final readonly class RemindCalibrations
{
    public function __construct(private Dispatcher $events) {}

    public function handle(?Carbon $at = null): int
    {
        $today = ($at ?? Carbon::now())->copy()->startOfDay();
        $horizon = $today->copy()->addDays((int) config('projects.calibration_warning_days', 30));

        $due = Equipment::query()
            ->whereBetween('calibration_valid_until', [$today->toDateString(), $horizon->toDateString()])
            ->where(static function ($query): void {
                $query->whereNull('calibration_reminded_for')
                    ->orWhereColumn('calibration_reminded_for', '!=', 'calibration_valid_until');
            })
            ->get();

        $byUser = $due->groupBy('user_id');

        foreach ($byUser as $userId => $items) {
            foreach ($items as $item) {
                $item->update(['calibration_reminded_for' => $item->calibration_valid_until]);
            }

            /** @var Carbon $earliest */
            $earliest = $items->min('calibration_valid_until');

            $this->events->dispatch(new CalibrationDueSoon((int) $userId, $items->count(), $earliest));
        }

        return $byUser->count();
    }
}
