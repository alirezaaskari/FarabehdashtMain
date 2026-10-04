<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

/**
 * چرخه هر مرحله: پرداخت‌نشده ← در امانت ← تحویل‌شده ← (اصلاح ← تحویل) ← آزادشده.
 * اعتراض (۲۱-۵) با رأی مدیر به آزادشده، برگشتی یا تقسیم‌شده می‌رسد؛ لغو به برگشتی.
 */
enum MilestoneStatus: string
{
    case Unpaid = 'unpaid';
    case Funded = 'funded';
    case Delivered = 'delivered';
    case Revising = 'revising';
    case Released = 'released';
    case Disputed = 'disputed';
    case Refunded = 'refunded';
    case Settled = 'settled';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'پرداخت‌نشده',
            self::Funded => 'پول در امانت، در حال انجام',
            self::Delivered => 'تحویل‌شده، منتظر تأیید کارفرما',
            self::Revising => 'در حال اصلاح',
            self::Released => 'آزادشده',
            self::Disputed => 'اعتراض، در بررسی مدیر',
            self::Refunded => 'برگشت به کیف پول کارفرما',
            self::Settled => 'تقسیم با رأی مدیر',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Unpaid => 'neutral',
            self::Funded, self::Released => 'primary',
            self::Delivered, self::Revising => 'caution',
            self::Disputed => 'danger',
            self::Refunded, self::Settled => 'neutral',
        };
    }

    /** پول این مرحله در امانت است. */
    public function isHeld(): bool
    {
        return in_array($this, [self::Funded, self::Delivered, self::Revising, self::Disputed], true);
    }
}
