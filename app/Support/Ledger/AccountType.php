<?php

declare(strict_types=1);

namespace App\Support\Ledger;

/**
 * نوع یک حساب در دفتر کل.
 *
 * در `app/Support` است، نه داخل ماژول `Ledger`: هر ماژولی که بخواهد تراکنش
 * مالی ثبت کند باید بتواند نوع حساب طرف تراکنش را مشخص کند (مثلاً «بدهکار
 * کیف پول کاربر، بستانکار درآمد پلتفرم»)، و قاعده ۱ وارد کردن این Enum از
 * داخل فضای‌نام ماژول Ledger را برای مصرف‌کننده‌ها ممنوع می‌کرد.
 *
 * پنج نوع اول دقیقاً همان چیزی است که ADR-0003 تصمیم‌گیری کرده. `Treasury`
 * ششمی است و توسط این بخش اضافه شده — ADR فقط پنج نوع را نام می‌برد و
 * هیچ‌کدام «طرف مقابل» شارژ دستی کیف پول نیست. شارژ دستی یعنی پول از بیرون
 * سیستم (واریز بانکی، نقد) وارد می‌شود؛ این پول باید یک‌جا برود تا تراکنش
 * متوازن بماند. تصمیم در `docs/decisions-pending.md` (DEC-20) ثبت شده و
 * منتظر تأیید مدیر است؛ تا آن زمان همین‌طور که هست کار می‌کند و هیچ رفتار
 * کاربر را عوض نمی‌کند.
 *
 * `ServiceEscrow` (بخش ۱۹-۱، DEC-49) پول خدمت مشاوره و بررسی گزارش را تا
 * پایان کار نگه می‌دارد؛ `ProjectEscrow` (بخش ۲۱-۱) پول هر مرحله بازار پروژه
 * را جدا نگه می‌دارد تا دو جریان با قاعده‌های متفاوت در یک حساب قاطی نشوند.
 */
enum AccountType: string
{
    case UserWallet = 'user_wallet';
    case PlatformRevenue = 'platform_revenue';
    case VendorPayable = 'vendor_payable';
    case ProjectEscrow = 'project_escrow';
    case GatewayClearing = 'gateway_clearing';
    case Treasury = 'treasury';
    case ServiceEscrow = 'service_escrow';

    public function label(): string
    {
        return match ($this) {
            self::UserWallet => 'کیف پول کاربر',
            self::PlatformRevenue => 'درآمد پلتفرم',
            self::VendorPayable => 'بدهی به فروشنده',
            self::ProjectEscrow => 'امانت وجه پروژه',
            self::GatewayClearing => 'حساب واسط درگاه',
            self::Treasury => 'خزانه پلتفرم',
            self::ServiceEscrow => 'امانت وجه خدمت',
        };
    }

    /**
     * آیا این نوع حساب موجودی کش‌شده دارد.
     *
     * فقط کیف پول در این بخش موجودی کش‌شده و جدول خودش را دارد. بقیه انواع
     * تا وقتی ماژولی به موجودی فوری‌شان نیاز داشته باشد، از جمع مستقیم
     * entries خوانده می‌شوند.
     */
    public function hasCachedBalance(): bool
    {
        return $this === self::UserWallet;
    }
}
