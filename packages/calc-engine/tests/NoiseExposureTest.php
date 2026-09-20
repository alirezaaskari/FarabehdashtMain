<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Engine;
use PHPUnit\Framework\TestCase;

/**
 * Leq، دز صدا و حذف زمینه — رفتار فراتر از موردهای مرجع.
 */
final class NoiseExposureTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = Engine::withDefaultFormulas();
    }

    public function test_leq_is_energy_weighted_not_arithmetic(): void
    {
        // میانگین حسابی ۹۰ و ۸۰ می‌شود ۸۵؛ میانگین انرژی بیشتر است چون بازه
        // پرصداتر سهم به‌مراتب بزرگ‌تری از انرژی دارد.
        $leq = $this->engine->run('equivalent-continuous-level', [
            'levels' => [90.0, 80.0],
            'durations' => [240.0, 240.0],
        ])->output('leq')->value;

        $this->assertGreaterThan(85.0, $leq);
        $this->assertEqualsWithDelta(87.4036268949, $leq, 1e-9);
    }

    public function test_doubling_the_exposure_time_at_the_criterion_level_doubles_the_dose(): void
    {
        $shared = ['levels' => [90.0], 'criterion_level' => 90.0, 'exchange_rate' => 5.0];

        $four = $this->engine->run('noise-dose', [...$shared, 'durations' => [4.0]]);
        $eight = $this->engine->run('noise-dose', [...$shared, 'durations' => [8.0]]);

        $this->assertEqualsWithDelta(50.0, $four->output('dose')->value, 1e-9);
        $this->assertEqualsWithDelta(100.0, $eight->output('dose')->value, 1e-9);
    }

    public function test_the_exchange_rate_changes_the_answer_for_the_same_measurement(): void
    {
        // چرا نرخ تبادل ورودی است و نه ثابت: با همان داده، دو مرجع دو عدد
        // متفاوت می‌دهند و ابزار حق ندارد یکی را بی‌صدا انتخاب کند.
        $shared = ['levels' => [95.0], 'durations' => [8.0], 'criterion_level' => 90.0];

        $threeDb = $this->engine->run('noise-dose', [...$shared, 'exchange_rate' => 3.0]);
        $fiveDb = $this->engine->run('noise-dose', [...$shared, 'exchange_rate' => 5.0]);

        $this->assertEqualsWithDelta(200.0, $fiveDb->output('dose')->value, 1e-9);
        $this->assertEqualsWithDelta(317.4802103936, $threeDb->output('dose')->value, 1e-9);
        $this->assertEqualsWithDelta(95.0, $fiveDb->output('twa')->value, 1e-9);
        $this->assertEqualsWithDelta(95.0, $threeDb->output('twa')->value, 1e-9);
    }

    public function test_an_overlong_shift_is_flagged(): void
    {
        $calculation = $this->engine->run('noise-dose', [
            'levels' => [85.0, 85.0],
            'durations' => [8.0, 4.0],
            'criterion_level' => 85.0,
            'exchange_rate' => 3.0,
        ]);

        $this->assertCount(1, $calculation->notes);
    }

    public function test_background_correction_is_the_inverse_of_the_logarithmic_sum(): void
    {
        $total = $this->engine->run('sound-pressure-sum', ['levels' => [90.0, 84.0]])->output('total')->value;

        $source = $this->engine->run('background-noise-correction', [
            'total' => $total,
            'background' => 84.0,
        ])->output('source')->value;

        $this->assertEqualsWithDelta(90.0, $source, 1e-9);
    }

    public function test_a_small_difference_is_flagged_as_unreliable(): void
    {
        $calculation = $this->engine->run('background-noise-correction', ['total' => 91.0, 'background' => 90.0]);

        $this->assertCount(1, $calculation->notes);
        $this->assertStringContainsString('حد بالا', $calculation->notes[0]);
    }

    public function test_a_large_difference_is_flagged_as_negligible(): void
    {
        $calculation = $this->engine->run('background-noise-correction', ['total' => 95.0, 'background' => 70.0]);

        $this->assertCount(1, $calculation->notes);
        $this->assertStringContainsString('ناچیز', $calculation->notes[0]);
    }
}
