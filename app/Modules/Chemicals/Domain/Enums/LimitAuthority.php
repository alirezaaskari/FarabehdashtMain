<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Enums;

/**
 * مرجعی که یک حد مواجهه را اعلام کرده.
 *
 * چرا Enum و نه رشته آزاد: جدول حدود مواجهه **چندمرجعی** است و کل ارزشش به
 * همین است که کاربر ببیند ACGIH چه می‌گوید و OEL ایران چه. اگر مرجع رشته آزاد
 * باشد، شش ماه بعد «ACGIH»، «acgih» و «ای‌سی‌جی‌آی‌اچ» سه ردیف جدا می‌شوند و
 * مقایسه از کار می‌افتد.
 *
 * ترتیب اعلام، ترتیب نمایش است: مرجع ایرانی اول می‌آید چون کاربر این سایت
 * کارشناس ایرانی است و تصمیمش را با آن می‌گیرد.
 */
enum LimitAuthority: string
{
    case IranOel = 'iran_oel';
    case Acgih = 'acgih';
    case Niosh = 'niosh';
    case Osha = 'osha';

    public function label(): string
    {
        return match ($this) {
            self::IranOel => 'حدود مجاز ایران',
            self::Acgih => 'ACGIH',
            self::Niosh => 'NIOSH',
            self::Osha => 'OSHA',
        };
    }

    /** نام کامل مرجع، برای صفحه ماده و خروجی چاپی. */
    public function fullName(): string
    {
        return match ($this) {
            self::IranOel => 'حدود مجاز مواجهه شغلی ایران — وزارت بهداشت، درمان و آموزش پزشکی',
            self::Acgih => 'American Conference of Governmental Industrial Hygienists',
            self::Niosh => 'National Institute for Occupational Safety and Health',
            self::Osha => 'Occupational Safety and Health Administration',
        };
    }

    /** آیا نام مرجع لاتین نوشته می‌شود — برای جهت متن و ارقام. */
    public function usesLatinScript(): bool
    {
        return $this !== self::IranOel;
    }
}
