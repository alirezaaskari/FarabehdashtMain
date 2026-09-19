<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * مبلغ، همیشه به تومان و همیشه عدد صحیح.
 *
 * قاعده قطعی پروژه (ADR-0003): واحد پایه تومان است و هیچ مبلغی در دامنه محصول
 * ریال نیست. تبدیل به ریال فقط داخل آداپتور درگاه پرداخت انجام می‌شود و همین
 * کلاس تنها راه ورود و خروج آن مرز است.
 *
 * چرا عدد صحیح: عدد اعشاری شناور در مسیر پول خطای گردکردن می‌سازد. هر ستون پولی
 * در دیتابیس BIGINT UNSIGNED است و پسوند `_toman` دارد.
 *
 * مبلغ منفی وجود ندارد: بدهکار و بستانکار در دفتر کل با جهت (direction) بیان
 * می‌شوند، نه با علامت عدد.
 */
final readonly class Money implements JsonSerializable, Stringable
{
    /** ضریب تبدیل تومان به ریال. فقط آداپتور درگاه حق استفاده دارد. */
    private const RIAL_FACTOR = 10;

    private function __construct(public int $toman)
    {
        if ($toman < 0) {
            throw new InvalidArgumentException('مبلغ منفی وجود ندارد؛ جهت تراکنش با debit و credit بیان می‌شود.');
        }
    }

    public static function toman(int $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * ساخت مبلغ از ورودی کاربر: ارقام فارسی، جداکننده هزارگان و فاصله پذیرفته می‌شود.
     *
     * @throws InvalidArgumentException اگر ورودی عدد صحیح تومان نباشد
     */
    public static function fromInput(string $input): self
    {
        $normalized = preg_replace('/[^0-9]/u', '', PersianDigits::toLatin($input)) ?? '';

        if ($normalized === '') {
            throw new InvalidArgumentException('مبلغ معتبر نیست.');
        }

        return new self((int) $normalized);
    }

    /**
     * تبدیل به ریال — **فقط داخل آداپتور درگاه پرداخت**.
     *
     * هیچ Model، Action، Service، جدول یا گزارشی این متد را صدا نمی‌زند.
     */
    public function toRialForGateway(): int
    {
        return $this->toman * self::RIAL_FACTOR;
    }

    /**
     * بازگرداندن مبلغ ریالی درگاه به تومان — نیمه دوم همان مرز.
     *
     * @throws InvalidArgumentException اگر مبلغ ریالی مضرب ده نباشد
     */
    public static function fromGatewayRial(int $rial): self
    {
        if ($rial % self::RIAL_FACTOR !== 0) {
            throw new InvalidArgumentException("مبلغ ریالی درگاه مضرب ده نیست: {$rial}");
        }

        return new self(intdiv($rial, self::RIAL_FACTOR));
    }

    public function plus(self $other): self
    {
        return new self($this->toman + $other->toman);
    }

    /**
     * @throws InvalidArgumentException اگر نتیجه منفی شود
     */
    public function minus(self $other): self
    {
        return new self($this->toman - $other->toman);
    }

    public function times(int $multiplier): self
    {
        return new self($this->toman * $multiplier);
    }

    /**
     * درصدی از مبلغ، با گردکردن به نزدیک‌ترین تومان.
     *
     * برای کمیسیون استفاده می‌شود. نرخ اعمال‌شده روی خود تراکنش ثبت می‌شود،
     * پس محاسبه دوباره آن هرگز لازم نیست.
     */
    public function percentage(float $percent): self
    {
        return new self((int) round($this->toman * $percent / 100));
    }

    public function isZero(): bool
    {
        return $this->toman === 0;
    }

    public function equals(self $other): bool
    {
        return $this->toman === $other->toman;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->toman > $other->toman;
    }

    public function isLessThan(self $other): bool
    {
        return $this->toman < $other->toman;
    }

    /** «۱۲٬۵۰۰ تومان» — برای نمایش به کاربر. */
    public function format(): string
    {
        return PersianNumber::format($this->toman).' تومان';
    }

    /** «۱۲٬۵۰۰» — وقتی واحد جداگانه نشان داده می‌شود. */
    public function formatWithoutUnit(): string
    {
        return PersianNumber::format($this->toman);
    }

    public function jsonSerialize(): int
    {
        return $this->toman;
    }

    public function __toString(): string
    {
        return (string) $this->toman;
    }
}
