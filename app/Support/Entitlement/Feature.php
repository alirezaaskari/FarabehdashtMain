<?php

declare(strict_types=1);

namespace App\Support\Entitlement;

/**
 * کاری که کاربر می‌خواهد انجام دهد و ممکن است به اشتراک نیاز داشته باشد.
 *
 * در `app/Support` است، نه داخل ماژول Monetization: ابزارها، پروژه‌ها و
 * گزارش‌ساز باید بتوانند بپرسند «اجازه این کار را دارد؟» بدون اینکه مدل
 * اشتراک را import کنند (قاعده ۱). خود ماژول درآمدزایی هم همین Enum را
 * می‌خواند تا بداند هر امکان زیر کدام جریان درآمدی است.
 *
 * موردی که ماژولش هنوز ساخته نشده (بایگانی گزارش، پرسش از متخصص) از حالا
 * این‌جا هست: افزودن case تازه بعداً یعنی دست‌زدن به نگاشت جریان‌ها و
 * پیکربندی سقف‌ها، و آن کار وقتی ماژولش نوشته می‌شود نباید لازم باشد.
 */
enum Feature: string
{
    case SaveCalculation = 'save_calculation';
    case CreateProject = 'create_project';
    case BuildReport = 'build_report';
    case ReportArchive = 'report_archive';
    case ShopDiscount = 'shop_discount';
    case PriorityQuestion = 'priority_question';

    public function label(): string
    {
        return match ($this) {
            self::SaveCalculation => 'ذخیره محاسبه',
            self::CreateProject => 'ساخت پروژه اندازه‌گیری',
            self::BuildReport => 'ساخت گزارش',
            self::ReportArchive => 'بایگانی نسخه‌های گزارش',
            self::ShopDiscount => 'تخفیف فروشگاه',
            self::PriorityQuestion => 'اولویت در پرسش از متخصص',
        };
    }
}
