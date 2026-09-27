<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * مرز ماژول مشاوره — قاعده ۱.
 */
final class ModuleBoundaryTest extends TestCase
{
    public function test_the_module_imports_no_model_from_another_module(): void
    {
        $offenders = [];

        foreach ($this->sourceFiles() as $file) {
            $source = (string) file_get_contents($file);

            preg_match_all('/^use (App\\\\Modules\\\\(?!Consulting)[^;]+);$/m', $source, $matches);

            foreach ($matches[1] as $import) {
                // قاعده ۱: رویداد ماژول دیگر را می‌شود شنید، مدلش را نه.
                if (str_ends_with($import, 'ServiceProvider') || preg_match('/^App\\\\Modules\\\\[A-Za-z]+\\\\Events\\\\/', $import) === 1) {
                    continue;
                }

                $offenders[] = basename($file).' → '.$import;
            }
        }

        $this->assertSame([], $offenders, 'ماژول مشاوره نباید کلاس ماژول دیگری را import کند.');
    }

    /** @return list<string> */
    private function sourceFiles(): array
    {
        $directory = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('app/Modules/Consulting')),
        );

        $files = [];

        foreach ($directory as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && ! str_contains($file->getPathname(), '/tests/')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
