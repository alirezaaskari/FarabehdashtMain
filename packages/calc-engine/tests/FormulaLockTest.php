<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Formulas\DefaultFormulas;
use Farabehdasht\CalcEngine\Verification\Fingerprint;
use Farabehdasht\CalcEngine\Verification\FormulaLock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * قفل رفتار — معیار پذیرش بخش ۶.
 *
 * اگر این تست قرمز شد، راهش به‌روز کردن مقدار قفل نیست. رفتار یک نسخه
 * منتشرشده عوض شده و باید کلاس نسخه تازه ساخته شود تا محاسبه‌های ذخیره‌شده
 * با نسخه خودشان بازتولید شوند.
 */
final class FormulaLockTest extends TestCase
{
    /**
     * @return iterable<string, array{Formula}>
     */
    public static function formulas(): iterable
    {
        foreach (DefaultFormulas::all() as $formula) {
            yield FormulaLock::keyFor($formula) => [$formula];
        }
    }

    #[DataProvider('formulas')]
    public function test_the_behaviour_of_a_released_version_is_locked(Formula $formula): void
    {
        $lock = FormulaLock::load();
        $key = FormulaLock::keyFor($formula);

        $this->assertTrue(
            $lock->has($formula),
            sprintf(
                'نسخه %s در قفل نیست. اگر نسخه تازه‌ای اضافه کرده‌اید، یک بار php packages/calc-engine/bin/lock-formulas.php را اجرا کنید.',
                $key,
            ),
        );

        $this->assertSame(
            $lock->fingerprintOf($formula),
            Fingerprint::of($formula),
            sprintf(
                'رفتار %s عوض شده ولی نسخه‌اش همان مانده. برای تغییر رابطه، کلاس نسخه تازه بسازید و نسخه قبلی را دست‌نخورده بگذارید.',
                $key,
            ),
        );
    }

    public function test_the_lock_has_no_entry_for_a_formula_that_no_longer_exists(): void
    {
        $registered = array_map(
            static fn (Formula $formula): string => FormulaLock::keyFor($formula),
            DefaultFormulas::all(),
        );

        // نسخه قدیمی حذف نمی‌شود؛ ردیف یتیم در قفل یعنی کلاسی پاک شده که
        // محاسبه‌های ذخیره‌شده ممکن است هنوز به آن ارجاع بدهند.
        $this->assertSame(
            [],
            array_diff(array_keys(FormulaLock::load()->entries()), $registered),
        );
    }
}
