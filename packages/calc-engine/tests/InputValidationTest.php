<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\Exception\InvalidInput;
use Farabehdasht\CalcEngine\Input\InputError;
use Farabehdasht\CalcEngine\Input\InputErrorCode;
use PHPUnit\Framework\TestCase;

/**
 * اعتبارسنجی ورودی — پیش از هر ضرب و جمعی.
 */
final class InputValidationTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = Engine::withDefaultFormulas();
    }

    public function test_a_missing_input_is_rejected(): void
    {
        $error = $this->errorFor('wbgt-indoor', ['natural_wet_bulb' => 25.0]);

        $this->assertSame('globe', $error->key);
        $this->assertSame(InputErrorCode::Missing, $error->code);
    }

    public function test_an_unknown_input_is_rejected(): void
    {
        // غلط تایپی در نام ورودی نباید خاموش بماند و مقدار پیش‌فرض بگیرد.
        $error = $this->errorFor('wbgt-indoor', [
            'natural_wet_bulb' => 25.0,
            'globe' => 35.0,
            'glob' => 35.0,
        ]);

        $this->assertSame('glob', $error->key);
        $this->assertSame(InputErrorCode::Unexpected, $error->code);
    }

    public function test_a_non_numeric_input_is_rejected(): void
    {
        $error = $this->errorFor('wbgt-indoor', ['natural_wet_bulb' => '25', 'globe' => 35.0]);

        $this->assertSame(InputErrorCode::NotNumeric, $error->code);
    }

    public function test_a_non_finite_input_is_rejected(): void
    {
        $error = $this->errorFor('wbgt-indoor', ['natural_wet_bulb' => NAN, 'globe' => 35.0]);

        $this->assertSame(InputErrorCode::NotFinite, $error->code);
    }

    public function test_a_value_outside_the_measurable_range_is_rejected(): void
    {
        $error = $this->errorFor('wbgt-indoor', ['natural_wet_bulb' => 500.0, 'globe' => 35.0]);

        $this->assertSame(InputErrorCode::OutOfRange, $error->code);
        $this->assertSame(['min' => -50.0, 'max' => 100.0, 'given' => 500.0], $error->params);
    }

    public function test_all_problems_come_back_together(): void
    {
        // کاربر باید یک بار فرم را اصلاح کند، نه چند بار.
        try {
            $this->engine->run('wbgt-outdoor', ['natural_wet_bulb' => 500.0]);
            $this->fail('انتظار InvalidInput می‌رفت.');
        } catch (InvalidInput $e) {
            $this->assertSame(['natural_wet_bulb', 'globe', 'dry_bulb'], $e->keys());
        }
    }

    public function test_a_list_input_rejects_a_single_value(): void
    {
        $error = $this->errorFor('sound-pressure-sum', ['levels' => 90.0]);

        $this->assertSame(InputErrorCode::ExpectedList, $error->code);
    }

    public function test_a_list_input_enforces_its_item_count(): void
    {
        $error = $this->errorFor('sound-pressure-sum', ['levels' => [90.0]]);

        $this->assertSame(InputErrorCode::TooFewItems, $error->code);

        $tooMany = $this->errorFor('sound-pressure-sum', ['levels' => array_fill(0, 65, 90.0)]);

        $this->assertSame(InputErrorCode::TooManyItems, $tooMany->code);
    }

    public function test_a_bad_item_inside_a_list_names_its_position(): void
    {
        $error = $this->errorFor('sound-pressure-sum', ['levels' => [90.0, 900.0]]);

        $this->assertSame('levels.1', $error->key);
        $this->assertSame(InputErrorCode::OutOfRange, $error->code);
    }

    public function test_a_single_input_rejects_a_list(): void
    {
        $error = $this->errorFor('wbgt-indoor', ['natural_wet_bulb' => [25.0], 'globe' => 35.0]);

        $this->assertSame(InputErrorCode::ExpectedSingle, $error->code);
    }

    public function test_integers_are_accepted_as_numbers(): void
    {
        // فرم HTML عدد صحیح می‌فرستد؛ رد کردنش آزار بی‌دلیل است.
        $calculation = $this->engine->run('wbgt-indoor', ['natural_wet_bulb' => 20, 'globe' => 20]);

        $this->assertEqualsWithDelta(20.0, $calculation->output('wbgt')->value, 1e-9);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function errorFor(string $formulaId, array $raw): InputError
    {
        try {
            $this->engine->run($formulaId, $raw);
        } catch (InvalidInput $e) {
            return $e->errors[0];
        }

        $this->fail('انتظار InvalidInput می‌رفت.');
    }
}
