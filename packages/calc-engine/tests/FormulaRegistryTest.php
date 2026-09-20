<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Exception\DuplicateFormula;
use Farabehdasht\CalcEngine\Exception\UnknownFormula;
use Farabehdasht\CalcEngine\FormulaRegistry;
use Farabehdasht\CalcEngine\Formulas\Wbgt\WbgtIndoorV1;
use Farabehdasht\CalcEngine\Tests\Fixtures\FakeFormula;
use PHPUnit\Framework\TestCase;

/**
 * نگهبان زمان‌اجرای قاعده نسخه‌گذاری.
 */
final class FormulaRegistryTest extends TestCase
{
    public function test_a_version_cannot_be_registered_twice(): void
    {
        $registry = new FormulaRegistry([new WbgtIndoorV1]);

        $this->expectException(DuplicateFormula::class);

        $registry->register(new WbgtIndoorV1);
    }

    public function test_versions_come_back_oldest_first(): void
    {
        // نسخه‌ها رشته‌اند، پس مرتب‌سازی باید معنایی باشد نه الفبایی:
        // «1.10.0» بعد از «1.9.0» می‌آید، نه قبلش.
        $registry = new FormulaRegistry([
            new FakeFormula('demo', '1.10.0'),
            new FakeFormula('demo', '1.9.0'),
            new FakeFormula('demo', '2.0.0'),
        ]);

        $this->assertSame(['1.9.0', '1.10.0', '2.0.0'], $registry->versionsOf('demo'));
    }

    public function test_without_a_version_the_latest_one_is_used(): void
    {
        $registry = new FormulaRegistry([
            new FakeFormula('demo', '1.9.0'),
            new FakeFormula('demo', '1.10.0'),
        ]);

        $this->assertSame('1.10.0', $registry->get('demo')->version());
    }

    public function test_an_old_version_is_still_reachable_by_name(): void
    {
        // محاسبه ذخیره‌شده باید با نسخه لحظه ثبتش بازتولید شود، نه با نسخه روز.
        $registry = new FormulaRegistry([
            new FakeFormula('demo', '1.0.0'),
            new FakeFormula('demo', '2.0.0'),
        ]);

        $this->assertSame('1.0.0', $registry->get('demo', '1.0.0')->version());
    }

    public function test_an_unknown_id_is_rejected(): void
    {
        $this->expectException(UnknownFormula::class);

        (new FormulaRegistry)->get('nothing');
    }

    public function test_an_unknown_version_is_rejected(): void
    {
        $registry = new FormulaRegistry([new WbgtIndoorV1]);

        $this->expectException(UnknownFormula::class);

        $registry->get('wbgt-indoor', '9.9.9');
    }

    public function test_has_reports_both_the_id_and_the_version(): void
    {
        $registry = new FormulaRegistry([new WbgtIndoorV1]);

        $this->assertTrue($registry->has('wbgt-indoor'));
        $this->assertTrue($registry->has('wbgt-indoor', '1.0.0'));
        $this->assertFalse($registry->has('wbgt-indoor', '2.0.0'));
        $this->assertFalse($registry->has('nothing'));
    }
}
