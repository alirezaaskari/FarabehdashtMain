<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain\Enums;

/** سطرهای «به اظهار خود کاربر» گذرنامه؛ هیچ‌کدام نشان تأیید ندارند. */
enum EntryKind: string
{
    case Education = 'education';
    case Work = 'work';
    case Certificate = 'certificate';

    public function label(): string
    {
        return match ($this) {
            self::Education => 'تحصیلات',
            self::Work => 'سابقه کار',
            self::Certificate => 'گواهی بیرونی',
        };
    }

    public function organizationLabel(): string
    {
        return match ($this) {
            self::Education => 'دانشگاه یا مؤسسه',
            self::Work => 'شرکت یا سازمان',
            self::Certificate => 'صادرکننده',
        };
    }
}
