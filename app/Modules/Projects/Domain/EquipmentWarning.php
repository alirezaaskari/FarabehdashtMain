<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

use App\Modules\Projects\Domain\Enums\CalibrationStatus;

/**
 * یک هشدار درباره تجهیزی که در پروژه به‌کار رفته.
 */
final readonly class EquipmentWarning
{
    public function __construct(
        public int $equipmentId,
        public string $identification,
        public CalibrationStatus $status,
        public ?string $validUntil,
        public int $readingCount,
    ) {}

    public function blocking(): bool
    {
        return $this->status->blocksReport();
    }

    public function message(): string
    {
        return match ($this->status) {
            CalibrationStatus::Expired => 'اعتبار کالیبراسیون این تجهیز گذشته است.',
            CalibrationStatus::NotRecorded => 'برای این تجهیز تاریخ اعتبار کالیبراسیون ثبت نشده است.',
            CalibrationStatus::ExpiringSoon => 'اعتبار کالیبراسیون این تجهیز به‌زودی تمام می‌شود.',
            CalibrationStatus::Valid => 'کالیبراسیون این تجهیز معتبر است.',
        };
    }
}
