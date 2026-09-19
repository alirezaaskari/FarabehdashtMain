<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use Stringable;

/**
 * شماره موبایل ایران، در یک قالب قطعی: 09xxxxxxxxx
 *
 * کاربر ممکن است ۰۹۱۲…، +۹۸۹۱۲…، 0098912… یا با ارقام فارسی بنویسد.
 * همه این‌ها یک شماره‌اند و باید یک‌جور ذخیره شوند، وگرنه «کاربر تکراری» می‌سازند.
 */
final readonly class Mobile implements Stringable
{
    private const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    private function __construct(public string $value) {}

    public static function fromInput(string $input): self
    {
        $normalized = self::normalize($input);

        if ($normalized === null) {
            throw new InvalidArgumentException('شماره موبایل معتبر نیست.');
        }

        return new self($normalized);
    }

    public static function tryFromInput(string $input): ?self
    {
        $normalized = self::normalize($input);

        return $normalized === null ? null : new self($normalized);
    }

    public static function isValid(string $input): bool
    {
        return self::normalize($input) !== null;
    }

    /** شماره در قالب 09xxxxxxxxx، یا null اگر معتبر نباشد. */
    public static function normalize(string $input): ?string
    {
        $digits = str_replace(
            [...self::PERSIAN_DIGITS, ...self::ARABIC_DIGITS],
            [...range(0, 9), ...range(0, 9)],
            trim($input),
        );

        $digits = preg_replace('/\D+/', '', $digits) ?? '';

        $digits = match (true) {
            str_starts_with($digits, '0098') => '0'.substr($digits, 4),
            str_starts_with($digits, '98') && strlen($digits) === 12 => '0'.substr($digits, 2),
            str_starts_with($digits, '9') && strlen($digits) === 10 => '0'.$digits,
            default => $digits,
        };

        return preg_match('/^09\d{9}$/', $digits) === 1 ? $digits : null;
    }

    /** نمایش نیمه‌پوشیده، برای جایی که شماره کامل نباید دیده شود. */
    public function masked(): string
    {
        return substr($this->value, 0, 4).'***'.substr($this->value, -4);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
