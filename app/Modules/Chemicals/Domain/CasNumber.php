<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain;

use InvalidArgumentException;

/**
 * شماره ثبت CAS، با رقم کنترلی‌اش.
 *
 * شماره CAS رقم کنترلی دارد: رقم آخر از مجموع وزنی بقیه ارقام به‌دست می‌آید.
 * یعنی یک اشتباه تایپی در ورود CSV **قابل تشخیص است** و لازم نیست تا روزی که
 * کارشناسی به ماده اشتباه استناد کند پنهان بماند.
 *
 *     108-88-3 (تولوئن) → 8×1 + 8×2 + 8×3 + 0×4 + 1×5 = 8+16+24+0+5 = 53
 *                       → 53 mod 10 = 3 ✓
 *
 * بدون این بررسی، «۱۰۸-۸۸-۴» یک شماره کاملاً معتبر به‌نظر می‌رسد و به ماده
 * دیگری اشاره می‌کند — یا به هیچ ماده‌ای.
 */
final readonly class CasNumber
{
    private function __construct(public string $value) {}

    /**
     * @throws InvalidArgumentException اگر قالب یا رقم کنترلی نادرست باشد
     */
    public static function fromString(string $raw): self
    {
        $normalised = self::normalise($raw);

        if (preg_match('/^(\d{2,7})-(\d{2})-(\d)$/', $normalised, $parts) !== 1) {
            throw new InvalidArgumentException(
                "قالب شماره CAS نادرست است: «{$raw}». قالب درست مثل 108-88-3 است.",
            );
        }

        $digits = $parts[1].$parts[2];
        $checkDigit = (int) $parts[3];

        if (self::checksum($digits) !== $checkDigit) {
            throw new InvalidArgumentException(
                "رقم کنترلی شماره CAS نمی‌خواند: «{$normalised}». احتمالاً اشتباه تایپی است.",
            );
        }

        return new self($normalised);
    }

    public static function isValid(string $raw): bool
    {
        try {
            self::fromString($raw);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * ارقام فارسی و خط تیره‌های غیرلاتین به شکل استاندارد برمی‌گردند.
     *
     * کاربری که «۱۰۸–۸۸–۳» را از یک سند فارسی کپی می‌کند، شماره درستی وارد
     * کرده؛ رد کردنش به‌خاطر شکل ارقام، سخت‌گیری بی‌فایده است.
     */
    private static function normalise(string $raw): string
    {
        $latin = strtr(trim($raw), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '–' => '-', '—' => '-', '−' => '-', '‐' => '-',
        ]);

        return preg_replace('/\s+/', '', $latin) ?? $latin;
    }

    /** مجموع وزنی ارقام از راست، به پیمانه ۱۰. */
    private static function checksum(string $digits): int
    {
        $sum = 0;
        $weight = 1;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum += ((int) $digits[$i]) * $weight;
            $weight++;
        }

        return $sum % 10;
    }
}
