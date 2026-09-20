<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Verification;

use Farabehdasht\CalcEngine\Formula;
use JsonException;
use RuntimeException;

/**
 * خواندن موردهای مرجع هر نسخه فرمول از پوشه golden.
 *
 * این داده‌ها عمداً بیرون از tests هستند: هم تست طلایی از آن‌ها می‌خواند و هم
 * اثر انگشت رفتاری فرمول از رویشان ساخته می‌شود، پس بخشی از قرارداد پکیج‌اند
 * نه داربست تست.
 */
final class GoldenVectors
{
    public static function directory(): string
    {
        return dirname(__DIR__, 2).'/golden';
    }

    public static function path(Formula $formula): string
    {
        return sprintf('%s/%s@%s.json', self::directory(), $formula->id(), $formula->version());
    }

    public static function exist(Formula $formula): bool
    {
        return is_file(self::path($formula));
    }

    /**
     * @return list<GoldenCase>
     *
     * @throws RuntimeException اگر فایل مرجع نباشد یا با فرمول نخواند
     */
    public static function for(Formula $formula): array
    {
        $path = self::path($formula);

        if (! is_file($path)) {
            throw new RuntimeException(sprintf(
                'فرمول «%s» نسخه «%s» فایل موردهای مرجع ندارد. انتظار می‌رفت در %s باشد.',
                $formula->id(),
                $formula->version(),
                $path,
            ));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('فایل موردهای مرجع خوانده نشد: %s', $path));
        }

        try {
            /** @var array{formula: string, version: string, cases: list<array<string, mixed>>} $data */
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('فایل موردهای مرجع JSON معتبر نیست: %s', $path), 0, $e);
        }

        if ($data['formula'] !== $formula->id() || $data['version'] !== $formula->version()) {
            throw new RuntimeException(sprintf(
                'فایل %s برای «%s@%s» است ولی «%s@%s» خوانده شد.',
                $path,
                $data['formula'],
                $data['version'],
                $formula->id(),
                $formula->version(),
            ));
        }

        return array_map(
            static fn (array $case): GoldenCase => new GoldenCase(
                name: (string) $case['name'],
                derivation: (string) $case['derivation'],
                inputs: (array) $case['inputs'],
                expected: array_map(floatval(...), (array) $case['expected']),
                tolerance: (float) $case['tolerance'],
            ),
            $data['cases'],
        );
    }
}
