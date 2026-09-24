<?php

declare(strict_types=1);

namespace App\Modules\Reports\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * قاعده ۱: گزارش‌ساز پروژه و ابزار را فقط از راه `ReportSource` می‌شناسد.
 */
final class ModuleBoundaryTest extends TestCase
{
    public function test_the_module_imports_nothing_but_events_from_other_modules(): void
    {
        $offenders = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('app/Modules/Reports')));

        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php') || str_contains($file->getPathname(), '/tests/')) {
                continue;
            }

            preg_match_all('/^use (App\\\\Modules\\\\(?!Reports)[^;]+);$/m', (string) file_get_contents($file->getPathname()), $matches);

            foreach ($matches[1] as $import) {
                if (preg_match('/^App\\\\Modules\\\\[A-Za-z]+\\\\Events\\\\/', $import) !== 1) {
                    $offenders[] = $file->getFilename().' → '.$import;
                }
            }
        }

        $this->assertSame([], $offenders, 'گزارش‌ساز جز رویداد، کلاسی از ماژول دیگر import نمی‌کند.');
    }
}
