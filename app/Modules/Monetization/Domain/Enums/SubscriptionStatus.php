<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain\Enums;

/**
 * وضعیت یک اشتراک.
 *
 * «لغوشده» یعنی تمدید خودکار متوقف شده، نه اینکه دسترسی همین حالا قطع شود:
 * کاربری که پول یک ماه را داده تا پایان همان ماه مشترک است. پس دسترسی از
 * `ends_at` خوانده می‌شود و این Enum فقط می‌گوید آیا دوره بعدی هم می‌آید.
 */
enum SubscriptionStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'فعال',
            self::Cancelled => 'لغوشده',
            self::Expired => 'منقضی',
        };
    }

    /** آیا تا وقتی `ends_at` نگذشته، دسترسی می‌دهد. */
    public function grantsAccessUntilEnd(): bool
    {
        return $this === self::Active || $this === self::Cancelled;
    }
}
