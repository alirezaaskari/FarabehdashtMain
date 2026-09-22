<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Tests;

use App\Modules\Chemicals\Domain\CasNumber;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CasNumberTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function validNumbers(): iterable
    {
        yield 'toluene' => ['108-88-3'];
        yield 'benzene' => ['71-43-2'];
        yield 'formaldehyde' => ['50-00-0'];
        yield 'crystalline silica' => ['14808-60-7'];
        yield 'xylene' => ['1330-20-7'];
        yield 'ammonia' => ['7664-41-7'];
        yield 'lead' => ['7439-92-1'];
        yield 'manganese' => ['7439-96-5'];
    }

    #[DataProvider('validNumbers')]
    public function test_real_cas_numbers_pass_the_checksum(string $cas): void
    {
        $this->assertTrue(CasNumber::isValid($cas));
        $this->assertSame($cas, (string) CasNumber::fromString($cas));
    }

    public function test_a_wrong_check_digit_is_rejected(): void
    {
        // ۱۰۸-۸۸-۳ (تولوئن) با آخرین رقم دستکاری‌شده — اشتباه تایپی محتمل.
        $this->assertFalse(CasNumber::isValid('108-88-4'));
    }

    public function test_a_malformed_string_is_rejected_with_a_clear_reason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/قالب/u');

        CasNumber::fromString('not-a-cas-number');
    }

    public function test_persian_digits_and_dashes_are_normalised(): void
    {
        $this->assertTrue(CasNumber::isValid('۱۰۸-۸۸-۳'));
        $this->assertSame('108-88-3', (string) CasNumber::fromString('۱۰۸–۸۸–۳'));
    }
}
