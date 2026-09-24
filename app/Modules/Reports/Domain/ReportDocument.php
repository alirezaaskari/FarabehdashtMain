<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain;

use App\Support\Reporting\ReportData;

/**
 * هر آنچه روی کاغذ گزارش می‌آید، و نه چیزی بیشتر.
 *
 * قالب PDF فقط همین شیء را می‌شناسد، نه مدل را. پیش‌نمایش آن را از
 * پیش‌نویس و داده تازه منبع می‌سازد و صدور همان را منجمد می‌کند؛ پس آنچه
 * کاربر در پیش‌نمایش دید، دقیقاً همان است که صادر می‌شود.
 */
final readonly class ReportDocument
{
    public const SCHEMA = 1;

    public function __construct(
        public string $title,
        public ?string $clientName,
        public ?string $site,
        public ?string $measuredOn,
        public string $authorName,
        public ?string $findings,
        public ?string $recommendations,
        public bool $includeEquipment,
        public bool $includeMethod,
        public ReportData $data,
        public int $revision = 1,
        public bool $calibrationAcknowledged = false,
        public ?string $trackingCode = null,
        public ?string $issuedOn = null,
        public ?string $supersedesCode = null,
    ) {}

    public static function fromDraft(Report $report, ReportData $data): self
    {
        return new self(
            title: (string) ($report->title ?? $data->suggestedTitle ?? ''),
            clientName: $report->client_name,
            site: $report->site,
            measuredOn: $report->measured_on,
            authorName: (string) $report->author_name,
            findings: $report->findings,
            recommendations: $report->recommendations,
            includeEquipment: $report->include_equipment,
            includeMethod: $report->include_method,
            data: $data,
            revision: $report->revision,
            supersedesCode: $report->supersedes?->tracking_code,
        );
    }

    public function issued(string $trackingCode, string $issuedOn, bool $calibrationAcknowledged): self
    {
        return new self(
            ...[...get_object_vars($this), 'trackingCode' => $trackingCode, 'issuedOn' => $issuedOn,
                'calibrationAcknowledged' => $calibrationAcknowledged],
        );
    }

    public function isPreview(): bool
    {
        return $this->trackingCode === null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['schema' => self::SCHEMA, ...get_object_vars($this), 'data' => $this->data->toArray()];
    }

    /** @param  array<string, mixed>  $snapshot */
    public static function fromArray(array $snapshot): self
    {
        $text = static fn (string $key): ?string => isset($snapshot[$key]) ? (string) $snapshot[$key] : null;

        return new self(
            title: (string) ($snapshot['title'] ?? ''),
            clientName: $text('clientName'),
            site: $text('site'),
            measuredOn: $text('measuredOn'),
            authorName: (string) ($snapshot['authorName'] ?? ''),
            findings: $text('findings'),
            recommendations: $text('recommendations'),
            includeEquipment: (bool) ($snapshot['includeEquipment'] ?? true),
            includeMethod: (bool) ($snapshot['includeMethod'] ?? true),
            data: ReportData::fromArray((array) ($snapshot['data'] ?? [])),
            revision: (int) ($snapshot['revision'] ?? 1),
            calibrationAcknowledged: (bool) ($snapshot['calibrationAcknowledged'] ?? false),
            trackingCode: $text('trackingCode'),
            issuedOn: $text('issuedOn'),
            supersedesCode: $text('supersedesCode'),
        );
    }
}
