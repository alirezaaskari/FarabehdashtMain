<?php

declare(strict_types=1);

namespace App\Support\Home;

/**
 * شکل نمایش یک بخش صفحه اصلی.
 *
 * وضعیت است، پس Enum است و نه رشته جادویی (قاعده ۵).
 */
enum HomeLayout: string
{
    /** کارت‌های سه‌ستونی: ابزار، مقاله، فایل، دوره. */
    case Cards = 'cards';

    /** ردیف‌های کوتاه کنار هم: نام ماده و شماره CAS. */
    case Chips = 'chips';

    /** فهرست ردیفی در نیمه صفحه: تازه‌های دانشنامه. */
    case List = 'list';

    /** پنل رنگی نیمه‌صفحه با کادر جست‌وجو و چیپ‌ها: بانک مواد. */
    case Panel = 'panel';

    /**
     * بخش نیمه‌عرض؛ دو بخش نیمه‌عرض پشت‌سرهم کنار هم می‌نشینند و تنها
     * مانده تمام‌عرض می‌شود.
     */
    public function isHalf(): bool
    {
        return $this === self::List || $this === self::Panel;
    }
}
