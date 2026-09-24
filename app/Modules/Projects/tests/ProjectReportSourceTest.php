<?php

declare(strict_types=1);

namespace App\Modules\Projects\Tests;

use App\Models\User;
use App\Modules\Projects\Actions\CreateProject;
use App\Modules\Projects\Actions\RecordReading;
use App\Modules\Projects\Domain\Enums\Industry;
use App\Modules\Projects\Domain\Equipment;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Reports\ProjectReportSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پروژه به‌عنوان منبع گزارش: درج خودکار تجهیز و وضعیت کالیبراسیونش.
 */
final class ProjectReportSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_readings_and_their_instruments_reach_the_report(): void
    {
        $user = User::factory()->create();
        $project = $this->projectWithReading($user, now()->subDay()->toDateString());

        $data = $this->source()->load($user->id, [$project->uuid]);

        $this->assertNotNull($data);
        $this->assertSame('دور اول', $data->measurements[0]->group);
        $this->assertSame('88', $data->measurements[0]->value);
        $this->assertSame('dB', $data->measurements[0]->unit);
        $this->assertCount(1, $data->equipment);
        $this->assertSame('SL-400', $data->equipment[0]->model);
        $this->assertSame('منقضی', $data->equipment[0]->calibrationStatus);
        $this->assertTrue($data->equipment[0]->blocking);
        $this->assertCount(1, $data->blockingEquipment());
    }

    public function test_only_the_owner_sees_or_loads_a_project(): void
    {
        $owner = User::factory()->create();
        $project = $this->projectWithReading($owner, null);
        $stranger = User::factory()->create();

        $this->assertCount(1, $this->source()->options($owner->id));
        $this->assertSame([], $this->source()->options($stranger->id));
        $this->assertNull($this->source()->load($stranger->id, [$project->uuid]));
    }

    public function test_a_project_without_readings_is_not_offered(): void
    {
        $user = User::factory()->create();
        $this->app->make(CreateProject::class)->handle($user, 'خالی', Industry::Foundry);

        $this->assertSame([], $this->source()->options($user->id));
    }

    private function projectWithReading(User $user, ?string $validUntil): Project
    {
        $project = $this->app->make(CreateProject::class)->handle($user, 'پایش صدا', Industry::Foundry);
        $equipment = Equipment::query()->create([
            'user_id' => $user->id,
            'name' => 'صداسنج',
            'model' => 'SL-400',
            'calibration_valid_until' => $validUntil,
        ]);

        $this->app->make(RecordReading::class)->manual(
            project: $project,
            round: $project->rounds()->first(),
            station: $project->stations()->first(),
            value: 88.0,
            unit: 'dB',
            equipmentId: $equipment->id,
        );

        return $project;
    }

    private function source(): ProjectReportSource
    {
        return $this->app->make(ProjectReportSource::class);
    }
}
