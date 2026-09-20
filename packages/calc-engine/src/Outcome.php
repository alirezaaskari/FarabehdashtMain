<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine;

/**
 * چیزی که یک فرمول برمی‌گرداند: مقادیر خام و یادداشت‌های کیفی.
 *
 * واحدها این‌جا نیستند؛ فرمول واحد خروجی‌هایش را یک‌بار در outputs() اعلام
 * می‌کند و موتور همان را به مقادیر می‌چسباند.
 */
final readonly class Outcome
{
    /**
     * @param  array<string, float>  $values
     * @param  list<string>  $notes  یادداشت کیفی درباره همین محاسبه — مثلاً تناقض در داده ورودی
     */
    public function __construct(
        public array $values,
        public array $notes = [],
    ) {}
}
