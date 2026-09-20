<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain;

/**
 * یک سطر نتیجه، آماده نمایش.
 *
 * عدد این‌جا از قبل رشته است: گرد کردن تصمیم لایه نمایش است و قالب نباید
 * خودش حساب کند (قاعده ۴).
 */
final readonly class ResultRow
{
    public function __construct(
        public string $key,
        public string $label,
        public string $value,
        public ?string $unit,
    ) {}
}
