<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain\Enums;

/**
 * دوره صورتحساب یک پلن.
 *
 * تعداد ماه از خود Enum می‌آید، نه ستون دیتابیس: ماه‌های یک دوره سالانه
 * تصمیم محصولی نیست که مدیر بخواهد عوضش کند، و ستون یعنی امکان ثبت پلن
 * «ماهانه ۱۲ ماهه».
 */
enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'ماهانه',
            self::Yearly => 'سالانه',
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Yearly => 12,
        };
    }
}
