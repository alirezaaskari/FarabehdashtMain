<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\Exception\InvalidInput;
use PHPUnit\Framework\TestCase;

/**
 * مرزهای فرمول‌های بخش ۱۸-۴ که موردهای مرجع (golden) نمی‌سنجند:
 * ورودی‌هایی که باید رد شوند و یادداشت‌هایی که باید بیایند.
 */
final class Section18ToolsTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        $this->engine = Engine::withDefaultFormulas();
    }

    public function test_a_mixture_needs_one_limit_per_component(): void
    {
        $this->expectException(InvalidInput::class);

        $this->engine->run('mixture-exposure-index-ppm', [
            'concentrations' => [10.0, 20.0, 5.0],
            'limits' => [50.0, 100.0],
        ]);
    }

    public function test_brief_scala_never_raises_a_limit(): void
    {
        $calculation = $this->engine->run('brief-scala-adjustment', ['shift_hours' => 6.0, 'weekly_hours' => 30.0]);

        $this->assertSame(1.0, $calculation->output('reduction_factor')->value);
        $this->assertNotEmpty($calculation->notes);
    }

    public function test_niosh_rejects_a_non_integer_coupling_code(): void
    {
        $this->expectException(InvalidInput::class);

        $this->engine->run('niosh-lifting', $this->lift(['coupling' => 1.5]));
    }

    public function test_niosh_rejects_a_duration_outside_its_three_groups(): void
    {
        $this->expectException(InvalidInput::class);

        $this->engine->run('niosh-lifting', $this->lift(['duration' => 4.0]));
    }

    public function test_niosh_rejects_a_frequency_whose_table_value_is_zero(): void
    {
        $this->expectException(InvalidInput::class);

        // ۱۲ بار در دقیقه در کار طولانی: FM = 0 در جدول.
        $this->engine->run('niosh-lifting', $this->lift(['frequency' => 12.0, 'duration' => 8.0]));
    }

    public function test_niosh_treats_a_frequency_below_the_first_row_as_the_first_row(): void
    {
        $calculation = $this->engine->run('niosh-lifting', $this->lift(['frequency' => 0.0]));

        $this->assertSame(1.0, $calculation->output('frequency_multiplier')->value);
    }

    public function test_vibration_rows_must_match_their_durations(): void
    {
        $this->expectException(InvalidInput::class);

        $this->engine->run('whole-body-vibration', [
            'x_axis' => [0.3, 0.4],
            'y_axis' => [0.2],
            'z_axis' => [0.5, 0.6],
            'durations' => [4.0, 4.0],
        ]);
    }

    public function test_moving_closer_to_the_source_is_flagged(): void
    {
        $calculation = $this->engine->run('noise-distance-attenuation', [
            'level' => 85.0,
            'measured_distance' => 4.0,
            'target_distance' => 1.0,
        ]);

        $this->assertNotEmpty($calculation->notes);
    }

    /**
     * @param  array<string, float>  $overrides
     * @return array<string, float>
     */
    private function lift(array $overrides): array
    {
        return [
            'load' => 10.0,
            'horizontal' => 25.0,
            'vertical' => 75.0,
            'travel' => 25.0,
            'asymmetry' => 0.0,
            'frequency' => 1.0,
            'duration' => 1.0,
            'coupling' => 1.0,
            ...$overrides,
        ];
    }
}
