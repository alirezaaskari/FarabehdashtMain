<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

/** وضعیت پیشنهاد یک مجری روی یک پروژه. */
enum BidStatus: string
{
    case Active = 'active';
    case Withdrawn = 'withdrawn';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'در انتظار انتخاب کارفرما',
            self::Withdrawn => 'پس گرفته شد',
            self::Accepted => 'پذیرفته شد',
            self::Declined => 'انتخاب نشد',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'caution',
            self::Accepted => 'primary',
            self::Withdrawn, self::Declined => 'neutral',
        };
    }
}
