<?php

declare(strict_types=1);

namespace App\Support\Admin;

use Filament\Support\Contracts\HasLabel;

/**
 * گروه‌های منوی پنل مدیریت.
 *
 * هر ماژول صفحه‌اش را خودش در یکی از این گروه‌ها می‌گذارد؛ ترتیب گروه‌ها
 * همان ترتیب case‌هاست. گروهی که هیچ
 * صفحه‌ای نداشته باشد (مثلاً چون ماژولش خاموش است) خودبه‌خود دیده نمی‌شود.
 */
enum NavigationGroup: string implements HasLabel
{
    case Review = 'review';
    case Content = 'content';
    case Finance = 'finance';
    case System = 'system';

    public function getLabel(): string
    {
        return match ($this) {
            self::Review => 'بررسی و تأیید',
            self::Content => 'محتوا و ابزار',
            self::Finance => 'مالی و درآمد',
            self::System => 'سامانه',
        };
    }
}
