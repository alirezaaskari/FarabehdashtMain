<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain\Enums;

/**
 * رفتار با مشترکان فعلی هنگام خاموش‌کردن یک جریان درآمدی.
 *
 * سه گزینه سند `monetization-toggles.md`. پیش‌فرض `RunToEnd` است — تصمیم
 * مدیر: کسی که پول داده تا پایان دوره‌اش دسترسی دارد و فقط تمدید خودکار
 * متوقف می‌شود.
 *
 * `ImmediateNoRefund` عمداً شرط دارد: فقط وقتی مجاز است که هیچ مشترک فعالی
 * نباشد. بدون این شرط، یک کلیک می‌توانست پول مردم را بخورد.
 */
enum ShutdownPolicy: string
{
    case RunToEnd = 'run_to_end';
    case RefundProrated = 'refund_prorated';
    case ImmediateNoRefund = 'immediate_no_refund';

    public function label(): string
    {
        return match ($this) {
            self::RunToEnd => 'تا پایان دوره فعال بمانند',
            self::RefundProrated => 'قطع فوری با بازگشت وجه نسبت‌به‌مدت',
            self::ImmediateNoRefund => 'قطع فوری بدون بازگشت وجه',
        };
    }

    /** آیا وجود مشترک فعال، این سیاست را غیرمجاز می‌کند. */
    public function forbidsActiveSubscribers(): bool
    {
        return $this === self::ImmediateNoRefund;
    }
}
