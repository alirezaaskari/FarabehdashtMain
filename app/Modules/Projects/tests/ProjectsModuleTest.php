<?php

declare(strict_types=1);

namespace App\Modules\Projects\Tests;

use App\Contracts\CalculationReader;
use App\Models\User;
use App\Modules\Projects\Actions\CreateProject;
use App\Modules\Projects\Actions\RecordReading;
use App\Modules\Projects\Domain\Enums\Industry;
use App\Modules\Projects\Domain\Equipment;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Services\IndustryTemplates;
use App\Modules\Projects\Services\MonitoringCalendar;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class ProjectsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_is_enabled(): void
    {
        $this->assertTrue($this->app->make(ModuleRegistry::class)->isEnabled('Projects'));
    }

    /**
     * @return iterable<string, array{Industry}>
     */
    public static function industries(): iterable
    {
        foreach (Industry::cases() as $industry) {
            yield $industry->value => [$industry];
        }
    }

    #[DataProvider('industries')]
    public function test_every_industry_has_a_template_with_stations_and_tools(Industry $industry): void
    {
        $template = $this->app->make(IndustryTemplates::class)->for($industry);

        $this->assertNotNull($template);
        $this->assertNotEmpty($template->stations);
        $this->assertNotEmpty($template->tools);
        $this->assertNotSame('', $template->note);
    }

    #[DataProvider('industries')]
    public function test_every_suggested_tool_actually_opens(Industry $industry): void
    {
        // درست‌بودن شناسه ابزار از راه HTTP بررسی می‌شود و نه با import کردن
        // ماژول ابزارها؛ HTTP مرز عمومی است، کلاس‌هایش نه (قاعده ۱).
        $template = $this->app->make(IndustryTemplates::class)->for($industry);

        foreach ($template->tools as $slug) {
            $this->get(route('tools.show', $slug))
                ->assertOk();
        }
    }

    public function test_a_template_creates_its_stations_and_two_rounds(): void
    {
        $project = $this->app->make(CreateProject::class)
            ->handle(User::factory()->create(), 'پایش', Industry::Hospital);

        $expected = $this->app->make(IndustryTemplates::class)->for(Industry::Hospital)->stations;

        $this->assertSame($expected, $project->stations()->pluck('title')->all());
        $this->assertCount(2, $project->rounds()->get());
    }

    public function test_a_project_without_an_industry_starts_empty(): void
    {
        $project = $this->app->make(CreateProject::class)->handle(User::factory()->create(), 'آزاد');

        $this->assertCount(0, $project->stations()->get());
        $this->assertCount(2, $project->rounds()->get());
    }

    public function test_a_reading_is_copied_from_a_saved_calculation_through_the_contract(): void
    {
        // ماژول پروژه‌ها مدل ماژول ابزارها را نمی‌شناسد؛ فقط قرارداد را.
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tools.calculations.store', 'wbgt-indoor'), [
            'natural_wet_bulb' => '25',
            'globe' => '35',
            'label' => 'ایستگاه یک',
        ])->assertRedirect();

        $reader = $this->app->make(CalculationReader::class);
        $stored = $reader->findForUser(
            (string) SavedCalculation::query()->value('uuid'),
            (int) $user->getKey(),
        );

        $this->assertNotNull($stored);
        $this->assertEqualsWithDelta(28.0, $stored->headlineValue, 1e-9);

        $project = $this->app->make(CreateProject::class)->handle($user, 'پایش گرمایی');
        $project->stations()->create(['title' => 'ایستگاه یک']);

        $reading = $this->app->make(RecordReading::class)->fromCalculation(
            project: $project,
            round: $project->rounds()->first(),
            station: $project->stations()->first(),
            calculationUuid: $stored->uuid,
        );

        $this->assertEqualsWithDelta(28.0, $reading->value, 1e-9);
        $this->assertSame('celsius', $reading->unit);
        $this->assertSame($stored->uuid, $reading->calculation_uuid);

        // محاسبه‌ای که قرائت از آن ساخته شده، با حذف کاربر فقط بایگانی می‌شود.
        $this->delete(route('tools.calculations.destroy', $stored->uuid))->assertRedirect();
        $this->assertNotNull($reader->findForUser($stored->uuid, (int) $user->getKey()));
        $this->assertNotNull(SavedCalculation::query()->where('uuid', $stored->uuid)->value('archived_at'));
    }

    public function test_a_calculation_of_another_user_is_refused(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('tools.calculations.store', 'wbgt-indoor'), [
            'natural_wet_bulb' => '25',
            'globe' => '35',
        ])->assertRedirect();

        $uuid = (string) SavedCalculation::query()->value('uuid');

        $intruder = User::factory()->create();
        $project = $this->app->make(CreateProject::class)->handle($intruder, 'پروژه مهاجم');
        $project->stations()->create(['title' => 'ایستگاه']);

        $this->expectException(RuntimeException::class);

        $this->app->make(RecordReading::class)->fromCalculation(
            project: $project,
            round: $project->rounds()->first(),
            station: $project->stations()->first(),
            calculationUuid: $uuid,
        );
    }

    public function test_an_archived_project_refuses_new_readings(): void
    {
        $project = $this->app->make(CreateProject::class)->handle(User::factory()->create(), 'بایگانی');
        $project->stations()->create(['title' => 'ایستگاه']);
        $project->update(['status' => 'archived']);

        $this->expectException(RuntimeException::class);

        $this->app->make(RecordReading::class)->manual(
            project: $project->fresh(),
            round: $project->rounds()->first(),
            station: $project->stations()->first(),
            value: 90.0,
            unit: 'dB',
        );
    }

    public function test_equipment_of_another_user_is_refused(): void
    {
        // مشخصات تجهیز در گزارش چاپ می‌شود؛ تجهیز دیگری نباید وارد پروژه شود.
        $project = $this->app->make(CreateProject::class)->handle(User::factory()->create(), 'پایش');
        $project->stations()->create(['title' => 'ایستگاه']);
        $foreign = Equipment::query()->create([
            'user_id' => User::factory()->create()->getKey(),
            'name' => 'صداسنج دیگری',
            'serial_number' => '999',
        ]);

        $this->expectException(RuntimeException::class);

        $this->app->make(RecordReading::class)->manual(
            project: $project,
            round: $project->rounds()->first(),
            station: $project->stations()->first(),
            value: 90.0,
            unit: 'dB',
            equipmentId: $foreign->getKey(),
        );
    }

    public function test_own_equipment_is_attached(): void
    {
        $user = User::factory()->create();
        $project = $this->app->make(CreateProject::class)->handle($user, 'پایش');
        $project->stations()->create(['title' => 'ایستگاه']);
        $own = Equipment::query()->create(['user_id' => $user->getKey(), 'name' => 'صداسنج']);

        $reading = $this->app->make(RecordReading::class)->manual(
            project: $project,
            round: $project->rounds()->first(),
            station: $project->stations()->first(),
            value: 90.0,
            unit: 'dB',
            equipmentId: $own->getKey(),
        );

        $this->assertSame($own->getKey(), $reading->equipment_id);
    }

    public function test_recording_twice_updates_the_same_cell(): void
    {
        // دو قرائت برای یک ایستگاه در یک دور یعنی داده مبهم.
        $project = $this->app->make(CreateProject::class)->handle(User::factory()->create(), 'پایش');
        $project->stations()->create(['title' => 'ایستگاه']);

        $record = $this->app->make(RecordReading::class);
        $round = $project->rounds()->first();
        $station = $project->stations()->first();

        $record->manual($project, $round, $station, 90.0, 'dB');
        $record->manual($project, $round, $station, 85.0, 'dB');

        $this->assertCount(1, $project->readings()->get());
        $this->assertEqualsWithDelta(85.0, $project->readings()->first()->value, 1e-9);
    }

    public function test_the_calendar_gathers_calibration_and_round_dates(): void
    {
        $user = User::factory()->create();

        Equipment::query()->create([
            'user_id' => $user->getKey(),
            'name' => 'صداسنج',
            'calibration_valid_until' => now()->addDays(20)->toDateString(),
        ]);

        $project = $this->app->make(CreateProject::class)->handle($user, 'پایش');
        $project->rounds()->first()->update(['measured_on' => now()->addDays(5)->toDateString()]);

        $entries = $this->app->make(MonitoringCalendar::class)->for((int) $user->getKey());

        $this->assertCount(2, $entries);
        // مرتب بر اساس تاریخ: دور زودتر از کالیبراسیون.
        $this->assertSame('round', $entries[0]->kind);
        $this->assertSame('calibration', $entries[1]->kind);
    }

    public function test_the_calendar_shows_overdue_items_too(): void
    {
        // تقویمی که فقط آینده را نشان بدهد، آنچه از دستتان رفته را پنهان می‌کند.
        $user = User::factory()->create();

        Equipment::query()->create([
            'user_id' => $user->getKey(),
            'name' => 'صداسنج',
            'calibration_valid_until' => now()->subDays(10)->toDateString(),
        ]);

        $entries = $this->app->make(MonitoringCalendar::class)->for((int) $user->getKey());

        $this->assertCount(1, $entries);
        $this->assertTrue($entries[0]->overdue);
    }

    public function test_the_calendar_is_private_to_its_owner(): void
    {
        $user = User::factory()->create();

        Equipment::query()->create([
            'user_id' => $user->getKey(),
            'name' => 'صداسنج',
            'calibration_valid_until' => now()->addDay()->toDateString(),
        ]);

        $this->assertSame([], $this->app->make(MonitoringCalendar::class)->for((int) User::factory()->create()->getKey()));
    }

    public function test_every_page_needs_a_signed_in_user(): void
    {
        $project = $this->app->make(CreateProject::class)->handle(User::factory()->create(), 'پایش');

        foreach ([
            route('projects.index'),
            route('projects.equipment.index'),
            route('projects.calendar'),
            route('projects.show', $project->uuid),
            route('projects.compare', $project->uuid),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_the_owner_sees_the_pages(): void
    {
        $user = User::factory()->create();
        $project = $this->app->make(CreateProject::class)->handle($user, 'پایش', Industry::Mining);

        $this->actingAs($user)->get(route('projects.index'))->assertOk()->assertSee('پایش');
        $this->actingAs($user)->get(route('projects.show', $project->uuid))->assertOk();
        $this->actingAs($user)->get(route('projects.equipment.index'))->assertOk();
        $this->actingAs($user)->get(route('projects.calendar'))->assertOk();
    }

    public function test_a_project_is_created_from_the_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('projects.store'), [
            'title' => 'پایش روشنایی',
            'industry' => Industry::Textile->value,
        ])->assertRedirect();

        $project = Project::query()->firstOrFail();

        $this->assertSame('پایش روشنایی', $project->title);
        $this->assertSame(Industry::Textile, $project->industry);
        $this->assertNotEmpty($project->stations()->get());
    }

    public function test_equipment_is_recorded_from_the_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('projects.equipment.store'), [
            'name' => 'صداسنج',
            'model' => 'SL-400',
            'calibration_valid_until' => now()->addYear()->toDateString(),
        ])->assertRedirect(route('projects.equipment.index'));

        $this->assertDatabaseHas('equipment', ['name' => 'صداسنج', 'user_id' => $user->getKey()]);
    }
}
