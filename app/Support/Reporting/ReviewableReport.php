<?php

declare(strict_types=1);

namespace App\Support\Reporting;

use DateTimeInterface;

/**
 * نسخه فقط‌خواندنی یک گزارش صادرشده برای بررسی متخصص.
 *
 * `html` را خود ماژول گزارش از Snapshot می‌سازد و همه متن کاربر در آن
 * escape شده است؛ فراخوان آن را همان‌طور نمایش می‌دهد و چیزی به آن نمی‌افزاید.
 */
final readonly class ReviewableReport
{
    /**
     * @param  array<string, string>  $sections  کلید بخش => عنوان، به ترتیب سند
     */
    public function __construct(
        public string $uuid,
        public string $title,
        public string $trackingCode,
        public DateTimeInterface $issuedAt,
        public bool $valid,
        public array $sections,
        public string $html,
    ) {}
}
