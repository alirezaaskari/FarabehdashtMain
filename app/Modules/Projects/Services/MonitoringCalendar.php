<?php

declare(strict_types=1);

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Domain\CalendarEntry;
use App\Modules\Projects\Domain\Equipment;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Domain\ProjectRound;
use Illuminate\Support\Carbon;

/**
 * تقویم الزامات پایش.
 *
 * چیزی نمی‌سازد؛ فقط تاریخ‌هایی را که کاربر خودش ثبت کرده کنار هم می‌گذارد:
 * انقضای کالیبراسیون هر تجهیز، و تاریخ هر دور اندازه‌گیری.
 *
 * موارد گذشته هم می‌آیند — تقویمی که فقط آینده را نشان بدهد، همان چیزی را که
 * از دستتان رفته پنهان می‌کند.
 */
final readonly class MonitoringCalendar
{
    /**
     * @return list<CalendarEntry>
     */
    public function for(int $userId): array
    {
        $horizon = now()->addDays((int) config('projects.calendar_horizon_days', 180));

        return $this->sorted([
            ...$this->calibrationEntries($userId, $horizon),
            ...$this->roundEntries($userId, $horizon),
        ]);
    }

    /**
     * @return list<CalendarEntry>
     */
    private function calibrationEntries(int $userId, Carbon $horizon): array
    {
        $equipment = Equipment::query()
            ->forUser($userId)
            ->whereNotNull('calibration_valid_until')
            ->where('calibration_valid_until', '<=', $horizon)
            ->get();

        return $equipment->map(static function (Equipment $item): CalendarEntry {
            $due = $item->calibration_valid_until;

            return new CalendarEntry(
                kind: CalendarEntry::KIND_CALIBRATION,
                title: 'پایان اعتبار کالیبراسیون',
                detail: $item->identification(),
                dueOn: $due,
                overdue: $due->isPast(),
            );
        })->values()->all();
    }

    /**
     * @return list<CalendarEntry>
     */
    private function roundEntries(int $userId, Carbon $horizon): array
    {
        $rounds = ProjectRound::query()
            ->whereNotNull('measured_on')
            ->where('measured_on', '<=', $horizon)
            ->whereHas('project', static fn ($query) => $query->where('user_id', $userId))
            ->with('project')
            ->get();

        return $rounds->map(static function (ProjectRound $round): CalendarEntry {
            $project = $round->project;

            return new CalendarEntry(
                kind: CalendarEntry::KIND_ROUND,
                title: $round->title,
                detail: $project instanceof Project ? $project->title : '',
                dueOn: $round->measured_on,
                overdue: $round->measured_on->isPast(),
            );
        })->values()->all();
    }

    /**
     * @param  list<CalendarEntry>  $entries
     * @return list<CalendarEntry>
     */
    private function sorted(array $entries): array
    {
        usort($entries, static fn (CalendarEntry $a, CalendarEntry $b): int => $a->dueOn <=> $b->dueOn);

        return $entries;
    }
}
