<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Engine;
use PHPUnit\Framework\TestCase;

/**
 * رفتار کیفی دو رابطه WBGT — چیزهایی که عدد خروجی نمی‌گوید.
 */
final class WbgtTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = Engine::withDefaultFormulas();
    }

    public function test_the_indoor_relation_ignores_dry_bulb_temperature(): void
    {
        // اگر دمای خشک را هم بپذیرد یعنی رابطه اشتباه پیاده شده است.
        $this->assertArrayNotHasKey(
            'dry_bulb',
            $this->engine->registry()->get('wbgt-indoor')->inputs(),
        );
    }

    public function test_the_weights_of_each_relation_add_up_to_one(): void
    {
        // آزمون ساختاری: در محیط هم‌دما، WBGT باید دقیقاً همان دما باشد.
        $indoor = $this->engine->run('wbgt-indoor', ['natural_wet_bulb' => 27.0, 'globe' => 27.0]);
        $outdoor = $this->engine->run('wbgt-outdoor', [
            'natural_wet_bulb' => 27.0,
            'globe' => 27.0,
            'dry_bulb' => 27.0,
        ]);

        $this->assertEqualsWithDelta(27.0, $indoor->output('wbgt')->value, 1e-9);
        $this->assertEqualsWithDelta(27.0, $outdoor->output('wbgt')->value, 1e-9);
    }

    public function test_solar_load_changes_the_result_for_the_same_measurements(): void
    {
        $shared = ['natural_wet_bulb' => 25.0, 'globe' => 40.0];

        $indoor = $this->engine->run('wbgt-indoor', $shared);
        $outdoor = $this->engine->run('wbgt-outdoor', [...$shared, 'dry_bulb' => 30.0]);

        $this->assertNotEqualsWithDelta(
            $indoor->output('wbgt')->value,
            $outdoor->output('wbgt')->value,
            1e-6,
        );
    }

    public function test_an_impossible_reading_produces_a_note_but_still_computes(): void
    {
        // ابزار میدانی نباید جلوی ثبت داده را بگیرد؛ باید بگوید داده مشکوک است.
        $calculation = $this->engine->run('wbgt-indoor', ['natural_wet_bulb' => 30.0, 'globe' => 28.0]);

        $this->assertCount(1, $calculation->notes);
        $this->assertEqualsWithDelta(29.4, $calculation->output('wbgt')->value, 1e-9);
    }

    public function test_a_cool_globe_under_sunlight_produces_a_note(): void
    {
        $calculation = $this->engine->run('wbgt-outdoor', [
            'natural_wet_bulb' => 20.0,
            'globe' => 25.0,
            'dry_bulb' => 30.0,
        ]);

        $this->assertCount(1, $calculation->notes);
    }

    public function test_a_plausible_reading_produces_no_note(): void
    {
        $calculation = $this->engine->run('wbgt-outdoor', [
            'natural_wet_bulb' => 25.0,
            'globe' => 40.0,
            'dry_bulb' => 32.0,
        ]);

        $this->assertSame([], $calculation->notes);
    }

    public function test_the_result_never_claims_compliance(): void
    {
        $calculation = $this->engine->run('wbgt-indoor', ['natural_wet_bulb' => 30.0, 'globe' => 40.0]);

        $this->assertNotEmpty($calculation->limitations);

        foreach (['مجاز', 'ایمن', 'قبول', 'تأیید'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, implode(' ', $calculation->notes));
        }
    }
}
