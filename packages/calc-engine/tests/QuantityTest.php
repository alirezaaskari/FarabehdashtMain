<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Exception\UnitMismatch;
use Farabehdasht\CalcEngine\Quantity;
use Farabehdasht\CalcEngine\Unit;
use PHPUnit\Framework\TestCase;

/**
 * هیچ عددی بی‌واحد از موتور بیرون نمی‌رود.
 */
final class QuantityTest extends TestCase
{
    public function test_reading_a_value_requires_naming_the_expected_unit(): void
    {
        $quantity = Quantity::of(28.0, Unit::Celsius);

        $this->assertEqualsWithDelta(28.0, $quantity->in(Unit::Celsius), 1e-9);
    }

    public function test_reading_with_the_wrong_unit_throws(): void
    {
        $this->expectException(UnitMismatch::class);

        Quantity::of(28.0, Unit::Celsius)->in(Unit::Decibel);
    }

    public function test_two_quantities_with_different_units_are_never_equal(): void
    {
        $this->assertFalse(
            Quantity::of(90.0, Unit::Decibel)->equals(Quantity::of(90.0, Unit::Celsius)),
        );
    }

    public function test_every_unit_has_a_persian_label(): void
    {
        foreach (Unit::cases() as $unit) {
            $this->assertNotSame('', $unit->label());
        }
    }

    public function test_only_a_dimensionless_unit_may_have_an_empty_symbol(): void
    {
        // نماد خالی یعنی لایه نمایش نباید چیزی کنار عدد بگذارد. این فقط برای
        // کمیت بی‌بعد درست است؛ برای بقیه، نماد جاافتاده یک اشتباه است.
        foreach (Unit::cases() as $unit) {
            if ($unit->dimensionless()) {
                $this->assertSame('', $unit->symbol());

                continue;
            }

            $this->assertNotSame('', $unit->symbol());
        }
    }
}
