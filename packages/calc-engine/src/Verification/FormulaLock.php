<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Verification;

use Farabehdasht\CalcEngine\Formula;
use RuntimeException;

/**
 * قفل رفتار فرمول‌ها.
 *
 * برای هر نسخه فرمول یک اثر انگشت ثبت شده است. اگر رفتار یک نسخه عوض شود،
 * اثر انگشتش می‌خورد و تست قفل قرمز می‌شود. راه درست عبور از این قفل، به‌روز
 * کردن مقدار نیست؛ ساختن کلاس نسخه تازه است.
 *
 * این قفل همان چیزی است که معیار پذیرش بخش ۶ می‌خواهد: «تغییر فرمول بدون
 * نسخه جدید ممکن نیست».
 */
final readonly class FormulaLock
{
    /**
     * @param  array<string, string>  $entries  کلید «id@version» و مقدار اثر انگشت
     */
    private function __construct(private array $entries) {}

    public static function path(): string
    {
        return dirname(__DIR__, 2).'/formulas.lock.json';
    }

    public static function load(): self
    {
        $path = self::path();

        if (! is_file($path)) {
            throw new RuntimeException(sprintf('فایل قفل فرمول‌ها پیدا نشد: %s', $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('فایل قفل فرمول‌ها خوانده نشد: %s', $path));
        }

        /** @var array<string, string> $entries */
        $entries = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return new self($entries);
    }

    public static function keyFor(Formula $formula): string
    {
        return sprintf('%s@%s', $formula->id(), $formula->version());
    }

    public function has(Formula $formula): bool
    {
        return isset($this->entries[self::keyFor($formula)]);
    }

    public function fingerprintOf(Formula $formula): ?string
    {
        return $this->entries[self::keyFor($formula)] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * @param  array<string, string>  $entries
     */
    public static function write(array $entries): void
    {
        ksort($entries);

        $json = json_encode($entries, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents(self::path(), $json."\n");
    }
}
