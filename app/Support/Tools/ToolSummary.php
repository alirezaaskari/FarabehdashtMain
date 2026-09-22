<?php

declare(strict_types=1);

namespace App\Support\Tools;

/**
 * معرفی کوتاه یک ابزار محاسباتی، برای ماژول‌های دیگر.
 *
 * این تنها شکلی است که یک ابزار بیرون از ماژول خودش دیده می‌شود. عمداً فقط
 * رشته دارد و نه شیء دامنه: هر چه بیشتر از این عبور کند، مرز قاعده ۱ نازک‌تر
 * می‌شود و روزی یک ماژول به `ToolDefinition` وابسته می‌شود.
 */
final readonly class ToolSummary
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $summary,
        public string $reference,
        public string $version,
    ) {}
}
