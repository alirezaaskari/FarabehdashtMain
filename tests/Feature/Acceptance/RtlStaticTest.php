<?php

declare(strict_types=1);

namespace Tests\Feature\Acceptance;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/**
 * پذیرش راست‌به‌چپ (بخش ۱۷): هیچ قالبی خصوصیت فیزیکی چپ و راست ندارد.
 *
 * `ml-4` در صفحه راست‌چین فاصله را سمت اشتباه می‌گذارد و در نگاه اول دیده
 * نمی‌شود؛ فقط با منطقی‌ها (`ms-*`، `pe-*`، `start-*`، `text-end`) چیدمان با
 * جهت صفحه برمی‌گردد. تست روی متن قالب‌هاست تا کلاسی که هنوز در هیچ صفحه
 * نمونه‌ای رندر نشده هم گرفته شود. بررسی زمان اجرا (سرریز، عدد و واحد بدون
 * data-numeric) در `scripts/a11y-check.mjs` است.
 */
final class RtlStaticTest extends TestCase
{
    /** پیشوندهای پاسخ‌گو و حالت (md:، hover:، rtl:…) پیش از خود کلاس. */
    private const PHYSICAL = '/(?<![\w-])(?:[a-z0-9-]+:)*-?(?:m[lr]|p[lr]|left|right|border-[lr]|rounded-[lr]|rounded-[tb][lr]|scroll-m[lr]|scroll-p[lr]|text-(?:left|right)|float-(?:left|right)|clear-(?:left|right))(?:-[\w.\[\]\/%]+)?(?![\w-])/';

    public function test_no_template_uses_physical_left_or_right(): void
    {
        $offenders = [];
        $scanned = 0;

        foreach ($this->templates() as $file) {
            $scanned++;
            $source = (string) file_get_contents($file->getPathname());

            // فقط مقدار class و آرایه‌های @class/:class؛ متن فارسی و کد PHP نه.
            preg_match_all('/(?:class|:class)="([^"]*)"|@class\(\[(.*?)\]\)/s', $source, $matches);

            foreach (array_merge($matches[1], $matches[2]) as $classes) {
                if (preg_match_all(self::PHYSICAL, $classes, $found) > 0) {
                    $offenders[] = $this->relative($file).': '.implode(' ', array_unique($found[0]));
                }
            }
        }

        $this->assertGreaterThan(100, $scanned, 'باید همه قالب‌ها را دیده باشد.');
        $this->assertSame([], $offenders, "کلاس فیزیکی به‌جای منطقی:\n".implode("\n", $offenders));
    }

    public function test_the_pattern_catches_what_it_should_and_nothing_else(): void
    {
        foreach (['ml-4', 'md:pr-2', '-mr-1', 'text-left', 'hover:border-l-2', 'rounded-tl-lg', 'left-0', 'pl-[3px]'] as $bad) {
            $this->assertMatchesRegularExpression(self::PHYSICAL, $bad, $bad);
        }

        foreach (['ms-4', 'md:pe-2', 'text-end', 'start-0', 'border-s-2', 'rounded-ss-lg', 'px-gutter', 'my-2', 'prose', 'primary', 'text-lede'] as $good) {
            $this->assertDoesNotMatchRegularExpression(self::PHYSICAL, $good, $good);
        }
    }

    /** @return iterable<SplFileInfo> */
    private function templates(): iterable
    {
        foreach ([resource_path('views'), app_path('Modules')] as $root) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
                if ($file instanceof SplFileInfo && str_ends_with($file->getFilename(), '.blade.php')) {
                    yield $file;
                }
            }
        }
    }

    private function relative(SplFileInfo $file): string
    {
        return str_replace(base_path().'/', '', $file->getPathname());
    }
}
