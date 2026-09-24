<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use App\Support\Entitlement\Feature;

/**
 * چند مورد از یک امکان را این کاربر تا حالا مصرف کرده.
 *
 * شمارش برعکس جریان می‌کند: ماژول درآمدزایی حق ندارد `SavedCalculation` یا
 * `Project` را import کند (قاعده ۱)، پس هر ماژول خودش شمارنده‌اش را در
 * کانتینر با برچسب `MonetizationServiceProvider::QUOTA_COUNTERS` ثبت
 * می‌کند و لایه دسترسی بدون دانستن اینکه چه ماژول‌هایی وجود دارند جمع
 * می‌زند — همان الگوی `ApprovalQueueSource`.
 *
 * امکانی که هیچ شمارنده‌ای ندارد سقف ندارد: نبودِ شمارنده یعنی «این امکان
 * شمردنی نیست»، نه «صفر مورد مصرف شده».
 */
interface QuotaCounter
{
    public function feature(): Feature;

    public function countFor(User $user): int;
}
