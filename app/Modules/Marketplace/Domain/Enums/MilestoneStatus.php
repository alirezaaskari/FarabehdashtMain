<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

/**
 * چرخه هر مرحله: پرداخت‌نشده ← در امانت ← تحویل‌شده ← (اصلاح ← تحویل) ← آزادشده.
 */
enum MilestoneStatus: string
{
    case Unpaid = 'unpaid';
    case Funded = 'funded';
    case Delivered = 'delivered';
    case Revising = 'revising';
    case Released = 'released';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'پرداخت‌نشده',
            self::Funded => 'پول در امانت، در حال انجام',
            self::Delivered => 'تحویل‌شده، منتظر تأیید کارفرما',
            self::Revising => 'در حال اصلاح',
            self::Released => 'آزادشده',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Unpaid => 'neutral',
            self::Funded, self::Released => 'primary',
            self::Delivered, self::Revising => 'caution',
        };
    }

    /** پول این مرحله در امانت است. */
    public function isHeld(): bool
    {
        return in_array($this, [self::Funded, self::Delivered, self::Revising], true);
    }
}
