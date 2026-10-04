<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

/**
 * دو حالت بررسی پیام که مدیر در پنل بینشان کلید می‌زند (DEC-80). عوض‌کردن
 * حالت فقط روی پیام‌های بعدی اثر دارد.
 */
enum ReviewMode: int
{
    case Suspicious = 0;
    case All = 1;

    public function label(): string
    {
        return match ($this) {
            self::Suspicious => 'فقط پیام مشکوک',
            self::All => 'همه پیام‌ها',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Suspicious => 'پیام عادی همان لحظه می‌رسد؛ پیامی که شبیه شماره، ایمیل، لینک یا نام پیام‌رسان است تا تأیید شما نگه داشته می‌شود.',
            self::All => 'هیچ پیامی بی تأیید شما به طرف دیگر نمی‌رسد.',
        };
    }
}
