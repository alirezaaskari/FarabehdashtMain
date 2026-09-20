<?php

declare(strict_types=1);

namespace App\Modules\Projects\Tests;

use App\Models\User;
use App\Modules\Projects\Actions\AcknowledgeEquipmentWarning;
use App\Modules\Projects\Actions\CreateProject;
use App\Modules\Projects\Actions\RecordReading;
use App\Modules\Projects\Domain\Enums\CalibrationStatus;
use App\Modules\Projects\Domain\Enums\Industry;
use App\Modules\Projects\Domain\Equipment;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Events\EquipmentWarningAcknowledged;
use App\Modules\Projects\Services\ReportReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۸، نیمه اول:
 * «تجهیز منقضی پیش از صدور گزارش هشدار می‌دهد.»
 */
final class EquipmentGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private ReportReadiness $readiness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->readiness = $this->app->make(ReportReadiness::class);

        $this->project = $this->app->make(CreateProject::class)
            ->handle($this->user, 'پایش صدا', Industry::Foundry);
    }

    private function equipment(?string $validUntil): Equipment
    {
        return Equipment::query()->create([
            'user_id' => $this->user->getKey(),
            'name' => 'صداسنج',
            'model' => 'SL-400',
            'serial_number' => '12345',
            'calibration_valid_until' => $validUntil,
        ]);
    }

    private function reading(Equipment $equipment): void
    {
        $this->app->make(RecordReading::class)->manual(
            project: $this->project,
            round: $this->project->rounds()->first(),
            station: $this->project->stations()->first(),
            value: 88.0,
            unit: 'dB',
            equipmentId: (int) $equipment->getKey(),
        );
    }

    public function test_an_expired_calibration_blocks_the_report_until_acknowledged(): void
    {
        $this->reading($this->equipment(now()->subDay()->toDateString()));

        $warnings = $this->readiness->blockingWarnings($this->project);

        $this->assertCount(1, $warnings);
        $this->assertSame(CalibrationStatus::Expired, $warnings[0]->status);
        $this->assertTrue($this->readiness->requiresAcknowledgement($this->project));
        $this->assertFalse($this->readiness->ready($this->project));
    }

    public function test_a_missing_calibration_date_is_not_treated_as_valid(): void
    {
        // «ثبت‌نشده» با «معتبر» یکی نیست. اگر یکی بود، کافی بود کاربر تاریخ را
        // خالی بگذارد تا هشدار خاموش شود.
        $this->reading($this->equipment(null));

        $warnings = $this->readiness->blockingWarnings($this->project);

        $this->assertCount(1, $warnings);
        $this->assertSame(CalibrationStatus::NotRecorded, $warnings[0]->status);
    }

    public function test_a_valid_calibration_raises_nothing(): void
    {
        $this->reading($this->equipment(now()->addYear()->toDateString()));

        $this->assertSame([], $this->readiness->warnings($this->project));
        $this->assertTrue($this->readiness->ready($this->project));
    }

    public function test_an_expiring_calibration_warns_without_blocking(): void
    {
        // کارشناسی که فردا می‌رود میدان باید امروز بداند، ولی این هشدار
        // جلوی گزارش را نمی‌گیرد.
        $this->reading($this->equipment(now()->addDays(10)->toDateString()));

        $warnings = $this->readiness->warnings($this->project);

        $this->assertCount(1, $warnings);
        $this->assertSame(CalibrationStatus::ExpiringSoon, $warnings[0]->status);
        $this->assertFalse($warnings[0]->blocking());
        $this->assertTrue($this->readiness->ready($this->project));
    }

    public function test_acknowledging_clears_the_block_and_is_written_to_the_audit_log(): void
    {
        $this->reading($this->equipment(now()->subDay()->toDateString()));

        $this->app->make(AcknowledgeEquipmentWarning::class)
            ->handle($this->project, (int) $this->user->getKey());

        $this->assertTrue($this->readiness->ready($this->project->fresh()));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'projects.equipment_warning_acknowledged',
            'subject_id' => $this->project->uuid,
            'actor_id' => $this->user->getKey(),
        ]);
    }

    public function test_acknowledging_without_a_warning_is_refused(): void
    {
        $this->reading($this->equipment(now()->addYear()->toDateString()));

        $this->expectException(RuntimeException::class);

        $this->app->make(AcknowledgeEquipmentWarning::class)->handle($this->project, null);
    }

    public function test_a_new_reading_after_acknowledgement_asks_again(): void
    {
        // تأییدی که پیش از آخرین تغییر ثبت شده، درباره وضعیت فعلی چیزی
        // نمی‌گوید. وگرنه یک بار تأییدکردن همه هشدارهای آینده را خاموش می‌کرد.
        $this->reading($this->equipment(now()->subDay()->toDateString()));

        $this->travelTo(now()->addMinute());
        $this->app->make(AcknowledgeEquipmentWarning::class)->handle($this->project, null);
        $this->assertTrue($this->readiness->ready($this->project->fresh()));

        $this->travelTo(now()->addMinutes(5));
        $second = $this->equipment(now()->subDays(3)->toDateString());
        $this->app->make(RecordReading::class)->manual(
            project: $this->project,
            round: $this->project->rounds()->skip(1)->first(),
            station: $this->project->stations()->first(),
            value: 91.0,
            unit: 'dB',
            equipmentId: (int) $second->getKey(),
        );

        $this->assertTrue($this->readiness->requiresAcknowledgement($this->project->fresh()));
    }

    public function test_the_warning_and_its_acknowledgement_appear_on_the_project_page(): void
    {
        Event::fake([EquipmentWarningAcknowledged::class]);

        $this->reading($this->equipment(now()->subDay()->toDateString()));

        $this->actingAs($this->user)
            ->get(route('projects.show', $this->project->uuid))
            ->assertOk()
            ->assertSee('اعتبار کالیبراسیون این تجهیز گذشته است.')
            ->assertSee('می‌دانم و با همین شرایط ادامه می‌دهم');
    }

    public function test_equipment_without_a_reading_does_not_warn(): void
    {
        // تجهیزی که در این پروژه به‌کار نرفته، به گزارش این پروژه ربطی ندارد.
        $this->equipment(now()->subYear()->toDateString());

        $this->assertSame([], $this->readiness->warnings($this->project));
    }
}
