<?php

declare(strict_types=1);

namespace App\Modules\Reports\Tests;

use App\Contracts\EntitlementGate;
use App\Models\User;
use App\Modules\Projects\Actions\CreateProject;
use App\Modules\Projects\Actions\RecordReading;
use App\Modules\Projects\Domain\Enums\Industry;
use App\Modules\Projects\Domain\Equipment;
use App\Modules\Projects\Domain\Project;
use App\Modules\Reports\Actions\StartReport;
use App\Modules\Reports\Domain\Report;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Support\Entitlement\OpenGate;
use Illuminate\Support\Str;

/**
 * داده نمونه گزارش‌ساز: یک پروژه با دو قرائت و یک تجهیز، و محاسبه ذخیره‌شده.
 */
trait ReportFixtures
{
    /** مثل مشترک Pro — لایه دسترسی همیشه‌مجاز. */
    private function allowIssuing(): void
    {
        $this->app->instance(EntitlementGate::class, new OpenGate);
    }

    private function project(User $user, ?string $validUntil = '+1 year'): Project
    {
        $project = $this->app->make(CreateProject::class)->handle($user, 'پایش صدای سالن پرس', Industry::Foundry, 'شرکت نمونه');

        $equipment = Equipment::query()->create([
            'user_id' => $user->getKey(),
            'name' => 'صداسنج',
            'manufacturer' => 'Casella',
            'model' => 'CEL-633',
            'serial_number' => 'SN-4471',
            'accuracy_class' => 'Class 1',
            'calibrated_on' => now()->subMonth()->toDateString(),
            'calibration_valid_until' => $validUntil === null ? null : date('Y-m-d', (int) strtotime($validUntil)),
            'calibration_reference' => 'آزمایشگاه مرجع',
        ]);

        $record = $this->app->make(RecordReading::class);

        foreach ($project->rounds()->get() as $index => $round) {
            $record->manual(
                project: $project,
                round: $round,
                station: $project->stations()->first(),
                value: 91.4 - $index * 6,
                unit: 'dB',
                equipmentId: (int) $equipment->getKey(),
            );
        }

        return $project;
    }

    private function calculation(User $user, string $label = 'اتاق کنترل'): SavedCalculation
    {
        return SavedCalculation::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $user->getKey(),
            'tool_slug' => 'wbgt-indoor',
            'formula_id' => 'wbgt-indoor',
            'formula_version' => '1',
            'label' => $label,
            'inputs' => [],
            'outputs' => ['wbgt' => ['value' => 27.84, 'unit' => 'celsius']],
            'notes' => [],
        ]);
    }

    private function draftFromProject(User $user, ?Project $project = null): Report
    {
        $project ??= $this->project($user);

        return $this->app->make(StartReport::class)->handle($user, 'project', [$project->uuid]);
    }
}
