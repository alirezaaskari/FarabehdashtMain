<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\Exception\InvalidInput;
use Farabehdasht\CalcEngine\Input\InputErrorCode;
use PHPUnit\Framework\TestCase;

/**
 * بررسی رابطه بین ورودی‌ها — چیزی که اعتبارسنجی تک‌تک نمی‌گیرد.
 */
final class CrossValidationTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = Engine::withDefaultFormulas();
    }

    public function test_paired_lists_must_have_the_same_length(): void
    {
        // سه غلظت و دو مدت یعنی یک بازه بی‌مدت مانده؛ صفر گرفتنش داده را
        // بی‌صدا خراب می‌کند.
        $this->expectException(InvalidInput::class);

        $this->engine->run('twa-ppm', [
            'concentrations' => [10.0, 20.0, 30.0],
            'durations' => [120.0, 360.0],
        ]);
    }

    public function test_a_zero_total_duration_is_rejected_instead_of_dividing_by_zero(): void
    {
        try {
            $this->engine->run('twa-ppm', [
                'concentrations' => [10.0, 20.0],
                'durations' => [0.0, 0.0],
            ]);
            $this->fail('انتظار InvalidInput می‌رفت.');
        } catch (InvalidInput $e) {
            $this->assertSame(['durations'], $e->keys());
            $this->assertSame(InputErrorCode::OutOfRange, $e->errors[0]->code);
        }
    }

    public function test_background_louder_than_the_total_is_rejected(): void
    {
        // انرژی منفی یعنی لگاریتم بی‌معنا؛ باید خطای داده باشد نه NAN.
        try {
            $this->engine->run('background-noise-correction', ['total' => 85.0, 'background' => 88.0]);
            $this->fail('انتظار InvalidInput می‌رفت.');
        } catch (InvalidInput $e) {
            $this->assertSame(['background'], $e->keys());
        }
    }

    public function test_an_equal_background_is_rejected_too(): void
    {
        $this->expectException(InvalidInput::class);

        $this->engine->run('background-noise-correction', ['total' => 88.0, 'background' => 88.0]);
    }

    public function test_leq_and_noise_dose_enforce_the_same_pairing_rule(): void
    {
        foreach ([
            ['equivalent-continuous-level', ['levels' => [90.0, 85.0], 'durations' => [60.0]]],
            ['noise-dose', ['levels' => [90.0, 85.0], 'durations' => [4.0], 'criterion_level' => 90.0, 'exchange_rate' => 5.0]],
        ] as [$formulaId, $raw]) {
            try {
                $this->engine->run($formulaId, $raw);
                $this->fail(sprintf('انتظار InvalidInput برای «%s» می‌رفت.', $formulaId));
            } catch (InvalidInput $e) {
                $this->assertSame(['durations'], $e->keys());
            }
        }
    }

    public function test_a_valid_pairing_passes(): void
    {
        $calculation = $this->engine->run('twa-ppm', [
            'concentrations' => [100.0, 50.0],
            'durations' => [120.0, 360.0],
        ]);

        $this->assertEqualsWithDelta(62.5, $calculation->output('twa')->value, 1e-9);
    }
}
