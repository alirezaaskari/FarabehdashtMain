<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use App\Support\PersianDigits;
use InvalidArgumentException;
use Stringable;

/**
 * شماره شبای ایران: «IR» و ۲۴ رقم، با رقم کنترل ISO 13616 (باقی‌مانده ۹۷).
 *
 * ورودی کاربر با فاصله، خط تیره، ارقام فارسی یا بدون «IR» پذیرفته می‌شود.
 * رقم کنترل اشتباه تایپی را پیش از رسیدن به دست مدیر مالی می‌گیرد.
 */
final readonly class Sheba implements Stringable
{
    private function __construct(public string $value) {}

    public static function fromInput(string $input): self
    {
        $compact = strtoupper(preg_replace('/[\s\-]+/u', '', PersianDigits::toLatin(trim($input))) ?? '');

        if (preg_match('/^\d{24}$/', $compact) === 1) {
            $compact = 'IR'.$compact;
        }

        if (preg_match('/^IR\d{24}$/', $compact) !== 1 || ! self::checksumHolds($compact)) {
            throw new InvalidArgumentException('شماره شبا معتبر نیست؛ «IR» و ۲۴ رقم را از روی کارت یا اپ بانک دوباره بخوانید.');
        }

        return new self($compact);
    }

    /** برای نمایش به خود کاربر: فقط چهار رقم آخر پیدا است. */
    public function masked(): string
    {
        return 'IR** **** '.substr($this->value, -4);
    }

    /** برای مدیر مالی که باید دستی واریز کند: گروه‌های چهارتایی. */
    public function grouped(): string
    {
        return implode(' ', str_split($this->value, 4));
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function checksumHolds(string $iban): bool
    {
        // IR → 18 27، و چهار نویسه اول به ته می‌رود.
        $numeric = substr($iban, 4).'1827'.substr($iban, 2, 2);
        $remainder = 0;

        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 1;
    }
}
