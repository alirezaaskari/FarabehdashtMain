<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use App\Support\Entitlement\Decision;
use App\Support\Entitlement\Feature;

/**
 * آیا این کاربر اجازه انجام این کار را دارد.
 *
 * اصل معماری بخش ۱۴: هیچ صفحه و ابزاری نمی‌پرسد «آیا این کاربر مشترک است؟».
 * همه از همین یک در می‌پرسند «اجازه X را دارد؟». خاموش‌کردن یک جریان درآمدی
 * فقط پاسخ این لایه را عوض می‌کند، نه کد صفحه‌ها را.
 *
 * برخلاف بقیه قراردادهای بین‌ماژولی، این یکی همیشه بسته است: پیش‌فرضش
 * `OpenGate` در `AppServiceProvider` است و ماژول Monetization بازنویسی‌اش
 * می‌کند. پس مصرف‌کننده هیچ‌وقت `app()->bound()` نمی‌نویسد.
 *
 * `$user` می‌تواند null باشد — مهمان هم صفحه ابزار را می‌بیند و باید بداند
 * ذخیره‌کردن به حساب نیاز دارد.
 */
interface EntitlementGate
{
    public function decide(?User $user, Feature $feature): Decision;
}
