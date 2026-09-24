<?php

declare(strict_types=1);

namespace App\Support\Reporting;

/**
 * آنچه یک منبع به گزارش‌ساز می‌دهد، و آنچه لحظه صدور منجمد می‌شود.
 *
 * در `app/Support` است تا Projects و Tools بتوانند بسازندش بی‌آنکه ماژول
 * گزارش را بشناسند (قاعده ۱). شکل آرایه‌ای‌اش همان Snapshot گزارش صادرشده
 * است؛ پس هر فیلد تازه باید در `fromArray` پیش‌فرض داشته باشد تا گزارش‌های
 * قدیمی همچنان باز شوند.
 */
final readonly class ReportData
{
    /**
     * @param  list<ReportMeasurement>  $measurements
     * @param  list<ReportEquipment>  $equipment
     */
    public function __construct(
        public string $sourceTitle,
        public array $measurements,
        public array $equipment = [],
        public ?string $suggestedTitle = null,
        public ?string $suggestedClient = null,
    ) {}

    /** @return list<ReportEquipment> */
    public function blockingEquipment(): array
    {
        return array_values(array_filter(
            $this->equipment,
            static fn (ReportEquipment $item): bool => $item->blocking,
        ));
    }

    /** @return list<string> */
    public function formulas(): array
    {
        $formulas = array_filter(array_map(
            static fn (ReportMeasurement $m): ?string => $m->formula,
            $this->measurements,
        ));

        return array_values(array_unique($formulas));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sourceTitle' => $this->sourceTitle,
            'measurements' => array_map(static fn (ReportMeasurement $m): array => $m->toArray(), $this->measurements),
            'equipment' => array_map(static fn (ReportEquipment $e): array => $e->toArray(), $this->equipment),
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            sourceTitle: (string) ($data['sourceTitle'] ?? ''),
            measurements: array_values(array_map(
                static fn (mixed $row): ReportMeasurement => ReportMeasurement::fromArray((array) $row),
                (array) ($data['measurements'] ?? []),
            )),
            equipment: array_values(array_map(
                static fn (mixed $row): ReportEquipment => ReportEquipment::fromArray((array) $row),
                (array) ($data['equipment'] ?? []),
            )),
        );
    }
}
