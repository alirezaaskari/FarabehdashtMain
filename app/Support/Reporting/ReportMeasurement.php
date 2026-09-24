<?php

declare(strict_types=1);

namespace App\Support\Reporting;

/**
 * یک سطر جدول نتایج گزارش.
 *
 * مقدار از پیش قالب‌بندی‌شده و رشته است، نه عدد: گزارش صادرشده باید همان
 * چیزی را چاپ کند که لحظه صدور دیده شد، حتی اگر قاعده گردکردن بعداً عوض شود.
 *
 * `group` دور اندازه‌گیری یا ابزار است و `point` ایستگاه یا نام محاسبه.
 * `formula` شناسه و نسخه فرمول است (ADR-0005) تا گزارش سال‌ها بعد قابل
 * بازتولید باشد؛ قرائت دستی فرمول ندارد.
 */
final readonly class ReportMeasurement
{
    public function __construct(
        public string $group,
        public string $point,
        public ?string $parameter,
        public string $value,
        public ?string $unit,
        public ?string $formula = null,
        public ?int $equipmentId = null,
        public ?string $measuredOn = null,
    ) {}

    /** @return array<string, string|int|null> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            group: (string) ($data['group'] ?? ''),
            point: (string) ($data['point'] ?? ''),
            parameter: isset($data['parameter']) ? (string) $data['parameter'] : null,
            value: (string) ($data['value'] ?? ''),
            unit: isset($data['unit']) ? (string) $data['unit'] : null,
            formula: isset($data['formula']) ? (string) $data['formula'] : null,
            equipmentId: isset($data['equipmentId']) ? (int) $data['equipmentId'] : null,
            measuredOn: isset($data['measuredOn']) ? (string) $data['measuredOn'] : null,
        );
    }
}
