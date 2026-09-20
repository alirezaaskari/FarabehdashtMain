<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Tests;

use JsonException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * پکیج باید بدون Laravel سرپا بماند — معیار پذیرش بخش ۶.
 *
 * وابستگی به فریم‌ورک معمولاً بی‌سروصدا و با یک use بی‌ضرر وارد می‌شود، پس
 * این تست به‌جای اعتماد، متن فایل‌ها و composer.json را نگاه می‌کند.
 */
final class StandaloneTest extends TestCase
{
    private const array FORBIDDEN_NAMESPACES = [
        'Illuminate\\',
        'Laravel\\',
        'Filament\\',
        'App\\',
    ];

    private const array FORBIDDEN_HELPERS = [
        'config(',
        'app(',
        'env(',
        'trans(',
        '__(',
        'now(',
    ];

    public function test_the_source_never_touches_the_framework(): void
    {
        foreach ($this->sourceFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            foreach (self::FORBIDDEN_NAMESPACES as $namespace) {
                $this->assertStringNotContainsString(
                    $namespace,
                    $contents,
                    sprintf('%s به «%s» ارجاع داده است؛ موتور محاسبات باید مستقل بماند.', $file->getFilename(), $namespace),
                );
            }

            foreach (self::FORBIDDEN_HELPERS as $helper) {
                $this->assertStringNotContainsString(
                    $helper,
                    $contents,
                    sprintf('%s از کمک‌تابع «%s» استفاده کرده؛ این تابع بیرون از Laravel وجود ندارد.', $file->getFilename(), $helper),
                );
            }
        }
    }

    /**
     * @throws JsonException
     */
    public function test_the_package_requires_nothing_but_php(): void
    {
        $manifest = (string) file_get_contents(dirname(__DIR__).'/composer.json');

        /** @var array{require: array<string, string>} $composer */
        $composer = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['php'], array_keys($composer['require']));
    }

    public function test_the_source_has_no_side_effects_at_load_time(): void
    {
        foreach ($this->sourceFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            // موتور فقط تعریف می‌کند؛ چاپ، خواندن پرونده یا ساعت در زمان بارگذاری
            // یعنی محاسبه دیگر خالص نیست و تست طلایی بی‌معنا می‌شود.
            $this->assertStringNotContainsString('date_default_timezone_set', $contents);
            $this->assertStringNotContainsString('mt_rand', $contents);
            $this->assertStringNotContainsString('random_int', $contents);
        }
    }

    /**
     * @return list<SplFileInfo>
     */
    private function sourceFiles(): array
    {
        $files = [];

        /** @var iterable<SplFileInfo> $iterator */
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(dirname(__DIR__).'/src', RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        $this->assertNotEmpty($files);

        return $files;
    }
}
