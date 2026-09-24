<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Enums;

/**
 * چرخه عمر یک گزارش.
 *
 * فقط پیش‌نویس ویرایش می‌شود. «جایگزین‌شده» و «باطل‌شده» هر دو صادرشده‌اند و
 * صفحه تأییدشان همچنان باز است — گیرنده باید بفهمد سندش دیگر آخرین نسخه
 * نیست، نه اینکه با «پیدا نشد» روبه‌رو شود.
 */
enum ReportStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Superseded = 'superseded';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::Issued => 'صادرشده',
            self::Superseded => 'جایگزین‌شده',
            self::Revoked => 'باطل‌شده',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Issued => 'primary',
            self::Superseded => 'caution',
            self::Revoked => 'danger',
        };
    }

    public function wasIssued(): bool
    {
        return $this !== self::Draft;
    }
}
