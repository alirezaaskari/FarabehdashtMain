<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain;

use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * سهم روزهای مانده از بهای یک دوره — مبنای بازگشت وجه نسبت‌به‌مدت.
 *
 * محاسبه روی روز است نه ثانیه: کاربر «هفده روز مانده» را می‌فهمد و
 * می‌تواند خودش حساب کند؛ عددی که از ثانیه درآمده باشد قابل بررسی نیست.
 * دوره‌ای که هنوز شروع نشده کل بهایش را برمی‌گرداند.
 */
final class UnusedShare
{
    public static function of(int $priceToman, ?Carbon $startsAt, ?Carbon $endsAt, Carbon $at): Money
    {
        if ($startsAt === null || $endsAt === null) {
            return Money::zero();
        }

        // اختلاف روی مهر زمانی حساب می‌شود و نه با diffInDays: آن متد اعشار
        // برمی‌گرداند و «روز» این‌جا باید عدد صحیح باشد.
        $total = intdiv($endsAt->getTimestamp() - $startsAt->getTimestamp(), 86400);
        $left = intdiv($endsAt->getTimestamp() - $at->getTimestamp(), 86400);

        if ($total <= 0 || $left <= 0) {
            return Money::zero();
        }

        return Money::toman(intdiv($priceToman * min($left, $total), $total));
    }
}
