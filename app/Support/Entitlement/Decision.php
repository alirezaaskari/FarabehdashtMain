<?php

declare(strict_types=1);

namespace App\Support\Entitlement;

use InvalidArgumentException;

/**
 * پاسخ لایه دسترسی به یک پرسش.
 *
 * پاسخ یک شیء است نه بولین، چون صفحه به چیزی بیش از آری/نه نیاز دارد: پیام
 * فارسی، شمارش مصرف، و مقصد گذرگاه تبدیل. قاعده طراحی می‌گوید هر کامپوننت
 * شش حالت دارد؛ با بولین، حالت «سقف پر شده» و حالت «فقط مشترک» یکی می‌شدند.
 *
 * `allowed` ستون جدا ندارد و از خود دلیل خوانده می‌شود، تا هیچ‌وقت پاسخی
 * ساخته نشود که دلیلش با نتیجه‌اش نخواند.
 */
final readonly class Decision
{
    private function __construct(
        public EntitlementReason $reason,
        public string $message,
        public ?int $used,
        public ?int $limit,
        public ?string $upgradeUrl,
    ) {}

    public static function allow(EntitlementReason $reason, ?int $used = null, ?int $limit = null): self
    {
        if (! $reason->allows()) {
            throw new InvalidArgumentException(sprintf('دلیل «%s» اجازه نمی‌دهد.', $reason->value));
        }

        return new self($reason, '', $used, $limit, null);
    }

    public static function deny(
        EntitlementReason $reason,
        string $message,
        ?int $used = null,
        ?int $limit = null,
        ?string $upgradeUrl = null,
    ): self {
        if ($reason->allows()) {
            throw new InvalidArgumentException(sprintf('دلیل «%s» رد نیست.', $reason->value));
        }

        return new self($reason, $message, $used, $limit, $upgradeUrl);
    }

    public function allowed(): bool
    {
        return $this->reason->allows();
    }

    public function denied(): bool
    {
        return ! $this->allowed();
    }

    /**
     * چند مورد دیگر مانده — فقط وقتی سقفی در کار باشد.
     *
     * هیچ‌وقت منفی برنمی‌گردد: کاربری که پیش از سخت‌گیرشدن سقف، بیشتر از آن
     * ذخیره کرده بود نباید «منفی ۳ مورد مانده» ببیند.
     */
    public function remaining(): ?int
    {
        if ($this->limit === null || $this->used === null) {
            return null;
        }

        return max(0, $this->limit - $this->used);
    }
}
