<?php

declare(strict_types=1);

namespace App\Support\Ledger;

/**
 * جهت یک ردیف دفتر کل.
 *
 * در `app/Support` است، نه داخل ماژول `Ledger`: هر ماژولی که تراکنش مالی
 * ثبت می‌کند باید جهت هر ردیف را مشخص کند، پس این Enum بخشی از زبان مشترک
 * مرز است، نه جزئیات داخلی دفتر کل (همان دلیل {@see AccountType}).
 *
 * مبلغ خودش هرگز منفی نیست (قاعده Money)؛ جهت است که می‌گوید مبلغ به کدام
 * سمت حساب می‌رود. بدهکار حساب دارایی را زیاد می‌کند (خزانه با ورود پول)،
 * بستانکار حساب بدهی را زیاد می‌کند (کیف پول کاربر با شارژ). «حساب کاربر
 * شما بستانکار شد» همان زبانی است که بانک‌ها هم به‌کار می‌برند.
 */
enum EntryDirection: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Debit => 'بدهکار',
            self::Credit => 'بستانکار',
        };
    }

    /** علامت این جهت در جمع‌بندی موجودی — بستانکار مثبت، بدهکار منفی. */
    public function sign(): int
    {
        return match ($this) {
            self::Credit => 1,
            self::Debit => -1,
        };
    }
}
