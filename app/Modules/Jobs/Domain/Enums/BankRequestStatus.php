<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain\Enums;

/**
 * درخواست تماس کارفرما از بانک رزومه (۲۰-۵). فقط «پذیرفته» اعتبار را خرج
 * می‌کند؛ رد و بی‌پاسخی آن را برمی‌گرداند (DEC-72).
 */
enum BankRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پاسخ',
            self::Accepted => 'پذیرفته',
            self::Declined => 'رد',
            self::Expired => 'بی‌پاسخ ماند',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'caution',
            self::Accepted => 'primary',
            self::Declined => 'danger',
            self::Expired => 'neutral',
        };
    }

    /** درخواستی که یک اعتبار را نگه داشته یا خرج کرده است. */
    public function holdsCredit(): bool
    {
        return $this === self::Pending || $this === self::Accepted;
    }

    /** @return list<self> */
    public static function holding(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $status): bool => $status->holdsCredit()));
    }
}
