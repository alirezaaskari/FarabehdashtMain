<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * قاعده قطعی ADR-0003: واحد پایه تومان است و ریال فقط در مرز درگاه دیده می‌شود.
 */
final class MoneyTest extends TestCase
{
    public function test_it_stores_an_integer_number_of_toman(): void
    {
        $this->assertSame(12500, Money::toman(12500)->toman);
        $this->assertSame(0, Money::zero()->toman);
        $this->assertTrue(Money::zero()->isZero());
    }

    public function test_a_negative_amount_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::toman(-1);
    }

    public function test_subtraction_below_zero_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::toman(1000)->minus(Money::toman(1500));
    }

    /** @return iterable<string, array{string, int}> */
    public static function userInputs(): iterable
    {
        yield 'ساده' => ['12500', 12500];
        yield 'با جداکننده فارسی' => ['۱۲٬۵۰۰', 12500];
        yield 'با ارقام فارسی' => ['۱۲۵۰۰', 12500];
        yield 'با ویرگول لاتین' => ['12,500', 12500];
        yield 'با واحد' => ['۱۲٬۵۰۰ تومان', 12500];
        yield 'با فاصله' => [' 12 500 ', 12500];
    }

    #[DataProvider('userInputs')]
    public function test_it_reads_an_amount_the_way_a_user_writes_it(string $input, int $expected): void
    {
        $this->assertSame($expected, Money::fromInput($input)->toman);
    }

    public function test_input_without_any_digit_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromInput('رایگان');
    }

    public function test_the_gateway_boundary_converts_both_ways_without_loss(): void
    {
        $price = Money::toman(12500);

        $this->assertSame(125000, $price->toRialForGateway());
        $this->assertTrue($price->equals(Money::fromGatewayRial(125000)));
    }

    public function test_a_rial_amount_that_is_not_a_multiple_of_ten_is_refused(): void
    {
        // درگاهی که مبلغ غیرمضرب ده برگرداند، یا اشتباه کرده یا دستکاری شده.
        $this->expectException(InvalidArgumentException::class);

        Money::fromGatewayRial(125001);
    }

    public function test_arithmetic_stays_in_whole_toman(): void
    {
        $this->assertSame(3000, Money::toman(1000)->plus(Money::toman(2000))->toman);
        $this->assertSame(500, Money::toman(1500)->minus(Money::toman(1000))->toman);
        $this->assertSame(3000, Money::toman(1000)->times(3)->toman);
    }

    public function test_commission_rounds_to_the_nearest_toman(): void
    {
        $this->assertSame(1250, Money::toman(12500)->percentage(10)->toman);
        $this->assertSame(333, Money::toman(3333)->percentage(10)->toman);
        $this->assertSame(1, Money::toman(5)->percentage(10)->toman);
    }

    public function test_comparison(): void
    {
        $small = Money::toman(100);
        $big = Money::toman(200);

        $this->assertTrue($big->isGreaterThan($small));
        $this->assertTrue($small->isLessThan($big));
        $this->assertTrue($small->equals(Money::toman(100)));
    }

    public function test_it_is_displayed_in_persian_with_the_unit(): void
    {
        $this->assertSame('۱۲٬۵۰۰ تومان', Money::toman(12500)->format());
        $this->assertSame('۱۲٬۵۰۰', Money::toman(12500)->formatWithoutUnit());
    }

    public function test_it_serializes_as_a_plain_integer_of_toman(): void
    {
        $this->assertSame('12500', json_encode(Money::toman(12500)));
        $this->assertSame('12500', (string) Money::toman(12500));
    }
}
