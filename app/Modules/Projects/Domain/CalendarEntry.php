<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

use Illuminate\Support\Carbon;

/**
 * یک الزام پایش در تقویم.
 *
 * دو منبع دارد و هر دو واقعی‌اند، نه ساختگی: انقضای کالیبراسیون تجهیزات، و
 * دورهای اندازه‌گیری برنامه‌ریزی‌شده. تقویمی که رویداد الکی بسازد، کسی جدی‌اش
 * نمی‌گیرد.
 */
final readonly class CalendarEntry
{
    public const string KIND_CALIBRATION = 'calibration';

    public const string KIND_ROUND = 'round';

    public function __construct(
        public string $kind,
        public string $title,
        public string $detail,
        public Carbon $dueOn,
        public bool $overdue,
    ) {}

    public function daysAway(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->dueOn->copy()->startOfDay(), absolute: false);
    }

    public function tone(): string
    {
        return $this->overdue ? 'danger' : ($this->daysAway() <= 30 ? 'caution' : 'neutral');
    }
}
