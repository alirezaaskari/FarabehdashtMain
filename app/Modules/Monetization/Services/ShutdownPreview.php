<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * اثر خاموش‌کردن یک جریان، پیش از اینکه کسی دکمه را بزند.
 *
 * فقط جریان‌های اشتراکی مشترک دارند؛ برای بقیه، شمار مشترک و مبلغ بازگشت
 * صفر است و تنها عدد بامعنا تعداد صفحه‌های پنهان‌شونده است.
 *
 * تعداد صفحه‌ها از مسیرهای واقعاً ثبت‌شده شمرده می‌شود، نه از فهرست ثابت:
 * ماژولی که خاموش باشد مسیرش هم نیست، و عددی که ادعا کند سه صفحه پنهان
 * می‌شود در حالی که یکی‌شان اصلاً وجود ندارد، مدیر را گمراه می‌کند.
 */
final readonly class ShutdownPreview
{
    /** @var array<string, list<string>> */
    private const ROUTES = [
        'pro_subscription' => ['monetization.plans', 'monetization.checkout', 'monetization.upgrade'],
        'team_seat' => ['monetization.seats'],
        'file_sale' => ['commerce.index', 'commerce.show', 'commerce.cart'],
        'course_sale' => ['courses.index', 'courses.show'],
        'paid_report_builder' => ['reports.purchase'],
        'exam_pack' => ['exam_prep.purchase'],
    ];

    public function for(RevenueStream $stream, ?Carbon $at = null): ShutdownSummary
    {
        $at ??= Carbon::now();

        return new ShutdownSummary(
            stream: $stream,
            activeSubscribers: $this->subscriberCount($stream, $at),
            monthlyRevenue: $this->monthlyRevenue($stream, $at),
            hiddenPages: $this->hiddenPages($stream),
            refundNeeded: $this->refundNeeded($stream, $at),
        );
    }

    private function subscriberCount(RevenueStream $stream, Carbon $at): int
    {
        if ($stream !== RevenueStream::ProSubscription) {
            return 0;
        }

        return Subscription::query()->current($at)->count();
    }

    /**
     * درآمد ماهانه متأثر: قیمت هر اشتراک فعال، تقسیم بر ماه‌های دوره‌اش.
     *
     * سالانه و ماهانه با هم جمع نمی‌شوند مگر به ماه تبدیل شوند، وگرنه یک
     * مشترک سالانه دوازده برابر وزن می‌گرفت.
     */
    private function monthlyRevenue(RevenueStream $stream, Carbon $at): Money
    {
        if ($stream !== RevenueStream::ProSubscription) {
            return Money::zero();
        }

        $total = Money::zero();

        $subscriptions = Subscription::query()->current($at)->with('plan')->get();

        foreach ($subscriptions as $subscription) {
            $plan = $subscription->plan;

            if ($plan === null) {
                continue;
            }

            $total = $total->plus(Money::toman(intdiv($plan->price_toman, $plan->billing_cycle->months())));
        }

        return $total;
    }

    private function hiddenPages(RevenueStream $stream): int
    {
        $names = self::ROUTES[$stream->value] ?? [];

        return count(array_filter($names, static fn (string $name): bool => Route::has($name)));
    }

    private function refundNeeded(RevenueStream $stream, Carbon $at): Money
    {
        if ($stream !== RevenueStream::ProSubscription) {
            return Money::zero();
        }

        $total = Money::zero();

        $periods = SubscriptionPeriod::query()
            ->where('status', PeriodStatus::Paid)
            ->where('ends_at', '>', $at)
            ->get();

        foreach ($periods as $period) {
            $total = $total->plus($period->unusedValue($at));
        }

        return $total;
    }
}
