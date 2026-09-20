<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\Exception\InvalidInput;
use Farabehdasht\CalcEngine\Unit;
use PHPUnit\Framework\TestCase;

/**
 * روشنایی و تهویه — دو رابطه ساده، با حالت‌های مرزی که ساده نیستند.
 */
final class LightingAndVentilationTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = Engine::withDefaultFormulas();
    }

    public function test_perfect_uniformity_gives_a_ratio_of_one(): void
    {
        $calculation = $this->engine->run('illuminance-uniformity', ['readings' => [450.0, 450.0, 450.0]]);

        $this->assertEqualsWithDelta(1.0, $calculation->output('uniformity_min_average')->value, 1e-9);
        $this->assertEqualsWithDelta(1.0, $calculation->output('uniformity_min_max')->value, 1e-9);
        $this->assertSame([], $calculation->notes);
    }

    public function test_all_zero_readings_report_a_note_instead_of_a_silent_zero(): void
    {
        // تقسیم بر صفر نباید NAN بدهد و نباید بی‌صدا صفر شود؛ باید گفته شود.
        $calculation = $this->engine->run('illuminance-uniformity', ['readings' => [0.0, 0.0]]);

        $this->assertCount(1, $calculation->notes);
        $this->assertEqualsWithDelta(0.0, $calculation->output('uniformity_min_average')->value, 1e-9);
    }

    public function test_the_uniformity_ratio_carries_no_unit_symbol(): void
    {
        $unit = $this->engine
            ->run('illuminance-uniformity', ['readings' => [500.0, 400.0]])
            ->output('uniformity_min_average')
            ->unit;

        $this->assertSame(Unit::Ratio, $unit);
        $this->assertTrue($unit->dimensionless());
        $this->assertSame('', $unit->symbol());
    }

    public function test_air_changes_and_minutes_per_change_are_consistent(): void
    {
        $calculation = $this->engine->run('air-changes-per-hour', ['airflow' => 1500.0, 'volume' => 250.0]);

        $airChanges = $calculation->output('air_changes')->value;
        $minutes = $calculation->output('minutes_per_change')->value;

        $this->assertEqualsWithDelta(6.0, $airChanges, 1e-9);
        $this->assertEqualsWithDelta(60.0, $airChanges * $minutes, 1e-9);
    }

    public function test_zero_airflow_reports_a_note_instead_of_infinity(): void
    {
        $calculation = $this->engine->run('air-changes-per-hour', ['airflow' => 0.0, 'volume' => 200.0]);

        $this->assertCount(1, $calculation->notes);
        $this->assertEqualsWithDelta(0.0, $calculation->output('minutes_per_change')->value, 1e-9);
    }

    public function test_a_zero_volume_is_rejected_before_dividing(): void
    {
        $this->expectException(InvalidInput::class);

        $this->engine->run('air-changes-per-hour', ['airflow' => 500.0, 'volume' => 0.0]);
    }
}
