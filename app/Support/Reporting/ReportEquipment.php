<?php

declare(strict_types=1);

namespace App\Support\Reporting;

/**
 * مشخصات یک تجهیز، همان‌طور که در پیوست گزارش چاپ می‌شود.
 *
 * تاریخ‌ها رشته شمسی آماده چاپ‌اند. `blocking` یعنی کالیبراسیون معتبری ثبت
 * نشده و صدور گزارش تأیید صریح کاربر را می‌خواهد؛ تصمیمش با ماژول صاحب
 * تجهیز است، نه گزارش‌ساز.
 */
final readonly class ReportEquipment
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $manufacturer,
        public ?string $model,
        public ?string $serialNumber,
        public ?string $accuracyClass,
        public ?string $calibratedOn,
        public ?string $validUntil,
        public ?string $calibrationReference,
        public string $calibrationStatus,
        public bool $blocking,
    ) {}

    /** @return array<string, string|int|bool|null> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        $optional = static fn (string $key): ?string => isset($data[$key]) ? (string) $data[$key] : null;

        return new self(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            manufacturer: $optional('manufacturer'),
            model: $optional('model'),
            serialNumber: $optional('serialNumber'),
            accuracyClass: $optional('accuracyClass'),
            calibratedOn: $optional('calibratedOn'),
            validUntil: $optional('validUntil'),
            calibrationReference: $optional('calibrationReference'),
            calibrationStatus: (string) ($data['calibrationStatus'] ?? ''),
            blocking: (bool) ($data['blocking'] ?? false),
        );
    }
}
