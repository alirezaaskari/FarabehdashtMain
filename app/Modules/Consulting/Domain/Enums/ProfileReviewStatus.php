<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain\Enums;

/**
 * وضعیت آخرین ویرایشی که مشاور فرستاده است، نه خود صفحه عمومی؛ صفحه‌ای که
 * یک بار منتشر شده با رد ویرایش بعدی پایین نمی‌آید.
 */
enum ProfileReviewStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'هنوز فرستاده نشده',
            self::Pending => 'در انتظار تأیید',
            self::Approved => 'تأیید شد',
            self::Rejected => 'برگشت برای اصلاح',
        };
    }
}
