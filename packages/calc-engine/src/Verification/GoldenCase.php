<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Verification;

/**
 * یک مورد مرجع: ورودی مشخص، خروجی انتظار، و نوشته‌ای که می‌گوید آن خروجی از
 * کجا آمده تا خواننده بتواند با ماشین‌حساب بازبینی کند.
 */
final readonly class GoldenCase
{
    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, float>  $expected
     */
    public function __construct(
        public string $name,
        public string $derivation,
        public array $inputs,
        public array $expected,
        public float $tolerance,
    ) {}
}
