<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Calculation;
use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\FormulaRegistry;
use Farabehdasht\CalcEngine\Tests\Fixtures\FakeFormula;
use Farabehdasht\CalcEngine\Unit;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * رفتار خود موتور، جدا از هر رابطه‌ای.
 */
final class EngineTest extends TestCase
{
    public function test_the_result_carries_the_version_that_produced_it(): void
    {
        $engine = Engine::withDefaultFormulas();

        $calculation = $engine->run('wbgt-indoor', ['natural_wet_bulb' => 25.0, 'globe' => 35.0]);

        $this->assertSame('wbgt-indoor', $calculation->formulaId);
        $this->assertSame('1.0.0', $calculation->formulaVersion);
    }

    public function test_an_explicit_version_wins_over_the_latest_one(): void
    {
        $engine = new Engine(new FormulaRegistry([
            new FakeFormula('demo', '1.0.0', 2.0),
            new FakeFormula('demo', '2.0.0', 3.0),
        ]));

        $this->assertEqualsWithDelta(20.0, $engine->run('demo', ['x' => 10.0], '1.0.0')->output('y')->value, 1e-9);
        $this->assertEqualsWithDelta(30.0, $engine->run('demo', ['x' => 10.0])->output('y')->value, 1e-9);
    }

    public function test_outputs_carry_their_unit(): void
    {
        $engine = Engine::withDefaultFormulas();

        $output = $engine->run('sound-pressure-sum', ['levels' => [90.0, 90.0]])->output('total');

        $this->assertSame(Unit::Decibel, $output->unit);
        $this->assertSame('dB', $output->unit->symbol());
    }

    public function test_every_result_carries_the_disclaimer(): void
    {
        // قاعده محصولی: هیچ خروجی ابزاری ادعای تشخیص یا انطباق ندارد.
        $engine = Engine::withDefaultFormulas();

        foreach ($engine->registry()->all() as $formula) {
            $this->assertContains(
                Calculation::DISCLAIMER,
                $engine->run(
                    $formula->id(),
                    $this->sampleInputFor($formula->id()),
                    $formula->version(),
                )->disclaimers(),
            );
        }
    }

    public function test_the_stored_shape_is_enough_to_reproduce_the_calculation(): void
    {
        // معیار پذیرش بخش ۷ از همین‌جا شروع می‌شود: آن‌چه ذخیره می‌شود باید
        // شناسه و نسخه و ورودی‌ها را داشته باشد، نه فقط عدد خروجی را.
        $engine = Engine::withDefaultFormulas();

        $stored = $engine->run('wbgt-outdoor', [
            'natural_wet_bulb' => 25.0,
            'globe' => 35.0,
            'dry_bulb' => 30.0,
        ])->toArray();

        /** @var array<string, array{value: float, unit: string}> $storedInputs */
        $storedInputs = $stored['inputs'];

        /** @var array{id: string, version: string} $storedFormula */
        $storedFormula = $stored['formula'];

        $replayed = $engine->run(
            $storedFormula['id'],
            array_map(static fn (array $quantity): float => $quantity['value'], $storedInputs),
            $storedFormula['version'],
        );

        $this->assertSame($stored['outputs'], $replayed->toArray()['outputs']);
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleInputFor(string $formulaId): array
    {
        return match ($formulaId) {
            'wbgt-indoor' => ['natural_wet_bulb' => 25.0, 'globe' => 35.0],
            'wbgt-outdoor' => ['natural_wet_bulb' => 25.0, 'globe' => 35.0, 'dry_bulb' => 30.0],
            'sound-pressure-sum' => ['levels' => [90.0, 90.0]],
            default => throw new LogicException(sprintf('نمونه ورودی برای «%s» تعریف نشده است.', $formulaId)),
        };
    }
}
