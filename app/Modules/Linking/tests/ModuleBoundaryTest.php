<?php

declare(strict_types=1);

namespace App\Modules\Linking\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * مرز ماژول پیوند داخلی — قاعده ۱.
 *
 * موتور پیوند متن و صفحه همه ماژول‌های محتوایی را می‌بیند، ولی فقط از راه
 * قراردادهای `app/Contracts`؛ هیچ مدل ماژول دیگری را نمی‌شناسد.
 */
final class ModuleBoundaryTest extends TestCase
{
    public function test_the_module_imports_nothing_but_events_from_other_modules(): void
    {
        $offenders = [];

        foreach ($this->sourceFiles() as $file) {
            $source = (string) file_get_contents($file);

            preg_match_all('/^use (App\\\\Modules\\\\(?!Linking)[^;]+);$/m', $source, $matches);

            foreach ($matches[1] as $import) {
                if (preg_match('/^App\\\\Modules\\\\[A-Za-z]+\\\\Events\\\\/', $import) === 1) {
                    continue;
                }

                $offenders[] = basename($file).' → '.$import;
            }
        }

        $this->assertSame([], $offenders, 'ماژول پیوند جز رویداد، کلاسی از ماژول دیگر import نمی‌کند.');
    }

    /** @return list<string> */
    private function sourceFiles(): array
    {
        $directory = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('app/Modules/Linking')),
        );

        $files = [];

        foreach ($directory as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php') && ! str_contains($file->getPathname(), '/tests/')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
