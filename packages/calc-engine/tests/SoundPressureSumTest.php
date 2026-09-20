<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Engine;
use PHPUnit\Framework\TestCase;

/**
 * رفتار جمع لگاریتمی، فراتر از موردهای مرجع.
 */
final class SoundPressureSumTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = Engine::withDefaultFormulas();
    }

    public function test_levels_are_not_added_arithmetically(): void
    {
        // خطای رایج: ۹۰ + ۹۰ = ۱۸۰. جواب درست ۹۳ است.
        $total = $this->engine->run('sound-pressure-sum', ['levels' => [90.0, 90.0]])->output('total')->value;

        $this->assertEqualsWithDelta(93.0102999566, $total, 1e-9);
        $this->assertLessThan(94.0, $total);
    }

    public function test_the_order_of_the_sources_does_not_matter(): void
    {
        $ascending = $this->engine->run('sound-pressure-sum', ['levels' => [70.0, 80.0, 90.0]]);
        $descending = $this->engine->run('sound-pressure-sum', ['levels' => [90.0, 80.0, 70.0]]);

        $this->assertEqualsWithDelta(
            $ascending->output('total')->value,
            $descending->output('total')->value,
            1e-9,
        );
    }

    public function test_the_total_is_never_below_the_loudest_source(): void
    {
        $levels = [62.5, 71.0, 88.25, 83.0, 79.5];

        $total = $this->engine->run('sound-pressure-sum', ['levels' => $levels])->output('total')->value;

        $this->assertGreaterThan(max($levels), $total);
    }

    public function test_a_dominant_source_is_pointed_out(): void
    {
        // اطلاع مهندسی مفید: کار روی منابع ضعیف‌تر اثری ندارد.
        $calculation = $this->engine->run('sound-pressure-sum', ['levels' => [95.0, 70.0]]);

        $this->assertCount(1, $calculation->notes);
    }

    public function test_comparable_sources_produce_no_dominance_note(): void
    {
        $calculation = $this->engine->run('sound-pressure-sum', ['levels' => [90.0, 89.0]]);

        $this->assertSame([], $calculation->notes);
    }

    public function test_negative_levels_are_accepted(): void
    {
        // تراز با وزن‌دهی A در محیط بسیار ساکت می‌تواند منفی شود.
        $total = $this->engine->run('sound-pressure-sum', ['levels' => [-3.0, -3.0]])->output('total')->value;

        $this->assertEqualsWithDelta(-3.0 + 3.0102999566, $total, 1e-9);
    }
}
