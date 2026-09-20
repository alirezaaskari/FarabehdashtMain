<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Formulas\DefaultFormulas;
use Farabehdasht\CalcEngine\Verification\GoldenCase;
use Farabehdasht\CalcEngine\Verification\GoldenVectors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * تست طلایی — هر نسخه فرمول روی موردهای مرجعش.
 *
 * این تست چیزی درباره «درست بودن» رابطه نمی‌گوید؛ می‌گوید پیاده‌سازی همان
 * چیزی را می‌دهد که موردهای مرجع می‌گویند، با همان دقتی که آن‌جا اعلام شده.
 */
final class GoldenVectorTest extends TestCase
{
    /**
     * @return iterable<string, array{Formula, GoldenCase}>
     */
    public static function cases(): iterable
    {
        foreach (DefaultFormulas::all() as $formula) {
            foreach (GoldenVectors::for($formula) as $case) {
                $label = sprintf('%s@%s — %s', $formula->id(), $formula->version(), $case->name);

                yield $label => [$formula, $case];
            }
        }
    }

    #[DataProvider('cases')]
    public function test_the_formula_reproduces_its_reference_case(Formula $formula, GoldenCase $case): void
    {
        $engine = Engine::withDefaultFormulas();

        $calculation = $engine->run($formula->id(), $case->inputs, $formula->version());

        foreach ($case->expected as $key => $expected) {
            $this->assertEqualsWithDelta(
                $expected,
                $calculation->output($key)->value,
                $case->tolerance,
                sprintf('%s — %s (%s)', $case->name, $key, $case->derivation),
            );
        }
    }

    /**
     * @return iterable<string, array{Formula}>
     */
    public static function formulas(): iterable
    {
        foreach (DefaultFormulas::all() as $formula) {
            yield sprintf('%s@%s', $formula->id(), $formula->version()) => [$formula];
        }
    }

    #[DataProvider('formulas')]
    public function test_every_formula_version_has_reference_cases(Formula $formula): void
    {
        // فرمول بدون مورد مرجع یعنی عددی که هیچ‌کس بازبینی‌اش نکرده است.
        $this->assertTrue(
            GoldenVectors::exist($formula),
            sprintf('فرمول %s@%s فایل موردهای مرجع ندارد.', $formula->id(), $formula->version()),
        );

        $this->assertNotEmpty(GoldenVectors::for($formula));
    }

    #[DataProvider('formulas')]
    public function test_every_formula_version_names_its_source(Formula $formula): void
    {
        $reference = $formula->reference();

        $this->assertNotSame('', $reference->title);
        $this->assertNotSame('', $reference->relation);
        $this->assertNotSame('', $reference->publisher);
        $this->assertGreaterThan(1900, $reference->year);
        $this->assertNotEmpty($formula->limitations());
    }
}
