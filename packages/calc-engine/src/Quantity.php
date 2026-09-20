<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine;

use Farabehdasht\CalcEngine\Exception\UnitMismatch;

/**
 * یک عدد به‌همراه واحدش. تغییرناپذیر.
 *
 * خواندن مقدار فقط با ذکر واحد مورد انتظار ممکن است تا هیچ عددی بی‌واحد
 * از این کلاس بیرون نرود.
 */
final readonly class Quantity
{
    private function __construct(
        public float $value,
        public Unit $unit,
    ) {}

    public static function of(float $value, Unit $unit): self
    {
        return new self($value, $unit);
    }

    /**
     * مقدار خام، مشروط به این‌که واحد همان باشد که انتظار می‌رود.
     *
     * @throws UnitMismatch
     */
    public function in(Unit $expected): float
    {
        if ($this->unit !== $expected) {
            throw UnitMismatch::between($expected, $this->unit);
        }

        return $this->value;
    }

    public function equals(self $other, float $tolerance = 0.0): bool
    {
        return $this->unit === $other->unit
            && abs($this->value - $other->value) <= $tolerance;
    }

    /**
     * @return array{value: float, unit: string}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'unit' => $this->unit->value];
    }
}
