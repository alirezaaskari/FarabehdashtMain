<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Money;
use App\Support\Payments\RefundablePurchase;
use InvalidArgumentException;

/**
 * خریدی که صفحه عمومی بازگشت وجه ندارد (بسته راه‌حل، اشتراک، تیم، بسته آزمون،
 * صدور گزارش) و مدیر مالی از صفحه «بازگشت وجه خریدهای دیگر» برمی‌گرداند.
 *
 * ماژول صاحب خرید این قرارداد را با برچسب {@see TAG} ثبت می‌کند. پول همیشه به
 * کیف پول خریدار برمی‌گردد (DEC-56)، دسترسی همان خرید بسته می‌شود و ثبت دفتر
 * کل کار خود ماژول است، چون فقط او می‌داند پول پرداخت به کجا رفته بود.
 */
interface RefundablePurchases
{
    public const TAG = 'finance.refundable_purchases';

    /** نام نوع خرید برای مدیر، مثل «بسته آزمون». */
    public function label(): string;

    /** @return list<RefundablePurchase> خریدهای پولی اخیر این کاربر، تازه‌ترین اول */
    public function paidBy(int $userId): array;

    /**
     * برگرداندن پول به کیف پول و بستن دسترسی؛ همه یا هیچ.
     *
     * @throws InvalidArgumentException وقتی این خرید برگشت‌پذیر نیست
     */
    public function refund(string $uuid, int $actorId, string $reason): Money;
}
