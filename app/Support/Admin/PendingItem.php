<?php

declare(strict_types=1);

namespace App\Support\Admin;

use DateTimeInterface;

/**
 * یک مورد منتظر تصمیم مدیر.
 *
 * داشبورد تصمیم‌محور است نه آماری: سؤال اول مدیر «چند فروش داشتم» نیست،
 * «الان چه چیزی معطل من است» است. این DTO زبان مشترک همه ماژول‌هاست تا صف
 * تأیید یکپارچه باشد، نه ده فهرست جدا.
 */
final readonly class PendingItem
{
    /**
     * @param  string  $ability  توانایی لازم برای دیدن و تصمیم‌گیری، مثل `admin.content.review`
     * @param  string  $kind  نوع مورد، برای گروه‌بندی — مثل `article` یا `settlement`
     * @param  string  $title  چیزی که مدیر می‌بیند
     * @param  string  $url  نشانی صفحه بررسی
     * @param  DateTimeInterface|null  $waitingSince  از کِی معطل است
     * @param  string|null  $submittedBy  نام ارسال‌کننده
     */
    public function __construct(
        public string $ability,
        public string $kind,
        public string $title,
        public string $url,
        public ?DateTimeInterface $waitingSince = null,
        public ?string $submittedBy = null,
    ) {}
}
