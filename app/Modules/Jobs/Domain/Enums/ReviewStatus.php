<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain\Enums;

/**
 * وضعیت آخرین نسخه‌ای که کارفرما برای صفحه شرکت یا آگهی فرستاده، نه خود
 * صفحه عمومی؛ آنچه یک بار منتشر شده با رد ویرایش بعدی پایین نمی‌آید.
 */
enum ReviewStatus: string
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
