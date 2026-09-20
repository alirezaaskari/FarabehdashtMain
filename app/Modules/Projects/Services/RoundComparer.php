<?php

declare(strict_types=1);

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Domain\ComparisonRow;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Domain\ProjectReading;
use App\Modules\Projects\Domain\ProjectRound;
use App\Modules\Projects\Domain\RoundComparison;
use RuntimeException;

/**
 * ساخت مقایسه دو دور از قرائت‌های پروژه.
 *
 * یک بررسی جدی این‌جا هست: **دو دور با واحد متفاوت مقایسه نمی‌شوند.** مقایسه
 * دسی‌بل با لوکس عددی ممکن است و کاملاً بی‌معنا؛ نمودارش هم خوش‌شکل درمی‌آید
 * و همین خطرناکش می‌کند.
 */
final readonly class RoundComparer
{
    public function compare(Project $project, ProjectRound $before, ProjectRound $after): RoundComparison
    {
        $this->guardSameProject($project, $before, $after);

        if ($before->is($after)) {
            throw new RuntimeException('یک دور با خودش مقایسه نمی‌شود.');
        }

        /** @var array<int, ProjectReading> $beforeReadings */
        $beforeReadings = $before->readings()->get()->keyBy('project_station_id')->all();

        /** @var array<int, ProjectReading> $afterReadings */
        $afterReadings = $after->readings()->get()->keyBy('project_station_id')->all();

        $unit = $this->resolveUnit($beforeReadings, $afterReadings);

        $rows = [];

        foreach ($project->stations()->get() as $station) {
            $id = (int) $station->getKey();

            $rows[] = new ComparisonRow(
                station: $station->title,
                before: isset($beforeReadings[$id]) ? $beforeReadings[$id]->value : null,
                after: isset($afterReadings[$id]) ? $afterReadings[$id]->value : null,
                unit: $unit,
            );
        }

        return new RoundComparison($before, $after, $rows, $unit);
    }

    private function guardSameProject(Project $project, ProjectRound $before, ProjectRound $after): void
    {
        foreach ([$before, $after] as $round) {
            if ($round->project_id !== $project->getKey()) {
                throw new RuntimeException('دور انتخاب‌شده به این پروژه تعلق ندارد.');
            }
        }
    }

    /**
     * واحد مشترک دو دور.
     *
     * @param  array<int, ProjectReading>  $before
     * @param  array<int, ProjectReading>  $after
     */
    private function resolveUnit(array $before, array $after): string
    {
        $units = [];

        foreach ([...array_values($before), ...array_values($after)] as $reading) {
            $units[$reading->unit] = true;
        }

        $distinct = array_keys($units);

        if (count($distinct) > 1) {
            throw new RuntimeException(sprintf(
                'قرائت‌های این دو دور واحدهای متفاوتی دارند (%s) و مقایسه‌شان معنا ندارد.',
                implode('، ', $distinct),
            ));
        }

        return $distinct[0] ?? '';
    }
}
