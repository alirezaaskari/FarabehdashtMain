<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain;

/**
 * یک محاسبه از جلسه جاری کاربر: ورودی‌های ساده و عدد اصلی نتیجه.
 */
final readonly class SessionPoint
{
    /** @param  array<string, string>  $inputs */
    public function __construct(
        public array $inputs,
        public string $value,
        public ?string $unit,
    ) {}

    /** @return array{inputs: array<string, string>, value: string, unit: ?string} */
    public function toArray(): array
    {
        return ['inputs' => $this->inputs, 'value' => $this->value, 'unit' => $this->unit];
    }

    /** @param  array<mixed>  $data */
    public static function fromArray(array $data): ?self
    {
        if (! isset($data['value']) || ! is_string($data['value']) || ! is_array($data['inputs'] ?? null)) {
            return null;
        }

        return new self(
            array_map(strval(...), array_filter($data['inputs'], is_scalar(...))),
            $data['value'],
            isset($data['unit']) && is_string($data['unit']) ? $data['unit'] : null,
        );
    }
}
