<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\JalaliDate;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * تاریخ شمسی با افزونه intl، بدون بسته جانبی (ADR-0001).
 */
final class JalaliDateTest extends TestCase
{
    private function tehran(string $datetime): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime, new DateTimeZone('Asia/Tehran'));
    }

    public function test_the_intl_extension_is_available(): void
    {
        $this->assertTrue(extension_loaded('intl'), 'بدون intl تاریخ شمسی کار نمی‌کند.');
    }

    public function test_long_form(): void
    {
        $this->assertSame('۲۸ شهریور ۱۴۰۵', JalaliDate::long($this->tehran('2026-09-19 14:30:00')));
    }

    public function test_short_form(): void
    {
        $this->assertSame('۱۴۰۵/۰۶/۲۸', JalaliDate::short($this->tehran('2026-09-19 14:30:00')));
    }

    public function test_long_form_with_time(): void
    {
        $this->assertSame(
            '۲۸ شهریور ۱۴۰۵، ساعت ۱۴:۳۰',
            JalaliDate::longWithTime($this->tehran('2026-09-19 14:30:00')),
        );
    }

    public function test_day_and_month(): void
    {
        $this->assertSame('شنبه ۲۸ شهریور', JalaliDate::dayAndMonth($this->tehran('2026-09-19 09:00:00')));
    }

    public function test_numeric_parts_come_back_as_latin_integers(): void
    {
        $this->assertSame(
            ['year' => 1405, 'month' => 6, 'day' => 28],
            JalaliDate::parts($this->tehran('2026-09-19 14:30:00')),
        );
    }

    public function test_a_utc_timestamp_is_shown_in_tehran_time(): void
    {
        // ۲۱:۰۰ UTC یعنی ۰۰:۳۰ بامداد فردا در تهران — یعنی یک روز شمسی جلوتر.
        $utc = new DateTimeImmutable('2026-09-19 21:00:00', new DateTimeZone('UTC'));

        $this->assertSame('۲۹ شهریور ۱۴۰۵', JalaliDate::long($utc));
    }

    public function test_the_first_day_of_the_year(): void
    {
        $this->assertSame('۱ فروردین ۱۴۰۵', JalaliDate::long($this->tehran('2026-03-21 10:00:00')));
    }

    public function test_a_leap_year_keeps_the_last_day_of_esfand(): void
    {
        // ۱۴۰۳ سال کبیسه است و اسفندش ۳۰ روز دارد.
        $this->assertSame('۳۰ اسفند ۱۴۰۳', JalaliDate::long($this->tehran('2025-03-20 10:00:00')));
    }
}
