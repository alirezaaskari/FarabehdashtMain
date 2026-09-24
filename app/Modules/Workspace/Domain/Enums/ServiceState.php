<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain\Enums;

/**
 * وضعیت یک سرویس.
 *
 * `Operational` هیچ‌وقت روی یک رویداد ثبت نمی‌شود؛ وضعیت پیش‌فرض سرویسی است که
 * رویداد باز ندارد.
 */
enum ServiceState: string
{
    case Operational = 'operational';
    case Maintenance = 'maintenance';
    case Degraded = 'degraded';
    case Outage = 'outage';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'برقرار',
            self::Maintenance => 'نگهداری برنامه‌ریزی‌شده',
            self::Degraded => 'اختلال جزئی',
            self::Outage => 'قطعی',
        };
    }

    /** لحن نشان وضعیت در قالب. */
    public function tone(): string
    {
        return match ($this) {
            self::Operational => 'primary',
            self::Maintenance => 'neutral',
            self::Degraded => 'caution',
            self::Outage => 'danger',
        };
    }

    /** شدت، برای انتخاب بدترین وضعیت میان چند رویداد هم‌زمان. */
    public function severity(): int
    {
        return match ($this) {
            self::Operational => 0,
            self::Maintenance => 1,
            self::Degraded => 2,
            self::Outage => 3,
        };
    }

    public function worse(self $other): self
    {
        return $other->severity() > $this->severity() ? $other : $this;
    }

    /** @return list<self> */
    public static function reportable(): array
    {
        return [self::Maintenance, self::Degraded, self::Outage];
    }
}
