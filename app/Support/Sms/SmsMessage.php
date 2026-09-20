<?php

declare(strict_types=1);

namespace App\Support\Sms;

use InvalidArgumentException;

/**
 * یک پیامک، پیش از آنکه درایور تصمیم بگیرد چطور بفرستدش.
 *
 * چرا شیء و نه رشته: خط خدماتی ایران با «الگو» کار می‌کند. سامانه پیامکی
 * متن کامل را نمی‌گیرد؛ شناسه الگوی تأییدشده را می‌گیرد به‌علاوه **مقدار
 * متغیرها**. اگر قرارداد فقط یک رشته رندرشده پاس بدهد، درایور الگو راهی
 * ندارد جز اینکه کد را از دل جمله فارسی بیرون بکشد — که شکننده است و روزی
 * بی‌صدا می‌شکند.
 *
 * پس هر پیام هر دو را با خودش می‌برد:
 *
 *   `text`      متن کامل، برای درایورهایی که الگو ندارند (لاگ، خط عمومی)
 *   `template`  کلید الگو، مثل `otp`
 *   `values`    مقدار متغیرهای الگو، **به ترتیبِ خود الگو**
 *
 * این‌جا در `app/Support` است و نه داخل ماژول Identity، چون قرارداد
 * `SmsSender` زیرساخت مشترک است و ماژول اعلان‌ها هم از آن استفاده می‌کند.
 */
final readonly class SmsMessage
{
    /**
     * @param  list<string>  $values
     */
    private function __construct(
        public string $text,
        public ?string $template = null,
        public array $values = [],
    ) {}

    /**
     * پیام متن‌آزاد — بدون الگو.
     */
    public static function plain(string $text): self
    {
        return new self($text);
    }

    /**
     * پیام مبتنی بر الگو.
     *
     * `$text` متن معادل است و اختیاری نیست: اگر روزی درایور از الگو به خط
     * عمومی برگردد، یا در لاگ محیط آزمایشی دیده شود، باید چیزی خواندنی وجود
     * داشته باشد.
     *
     * `$values` عمداً `list` اعلام نشده: فراخوان ممکن است آرایه کلیددار
     * بدهد و ترتیب برای الگو حیاتی است، پس این‌جا صریحاً بازچینی می‌شود.
     *
     * @param  array<array-key, string|int>  $values
     */
    public static function template(string $template, array $values, string $text): self
    {
        if ($template === '') {
            throw new InvalidArgumentException('کلید الگوی پیامک نمی‌تواند خالی باشد.');
        }

        if ($values === []) {
            throw new InvalidArgumentException('الگوی پیامک دست‌کم یک مقدار متغیر می‌خواهد.');
        }

        return new self(
            text: $text,
            template: $template,
            values: array_values(array_map(strval(...), $values)),
        );
    }

    public function usesTemplate(): bool
    {
        return $this->template !== null;
    }

    /**
     * مقدارها، به شکلی که سامانه پیامکی می‌خواهد.
     *
     * جداکننده از پیکربندی می‌آید چون بین سرویس‌دهنده‌ها فرق دارد.
     */
    public function joinedValues(string $separator): string
    {
        return implode($separator, $this->values);
    }
}
