<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * مرز ماژول هسته مالی — قاعده ۱.
 */
final class ModuleBoundaryTest extends TestCase
{
    public function test_the_module_imports_no_model_from_another_module(): void
    {
        $offenders = [];

        foreach ($this->sourceFiles() as $file) {
            $source = (string) file_get_contents($file);

            preg_match_all('/^use (App\\\\Modules\\\\(?!Ledger)[^;]+);$/m', $source, $matches);

            foreach ($matches[1] as $import) {
                if (str_ends_with($import, 'ServiceProvider')) {
                    continue;
                }

                $offenders[] = basename($file).' → '.$import;
            }
        }

        $this->assertSame([], $offenders, 'ماژول هسته مالی نباید کلاس ماژول دیگری را import کند.');
    }

    /** @return list<string> */
    private function sourceFiles(): array
    {
        $directory = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('app/Modules/Ledger')),
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
