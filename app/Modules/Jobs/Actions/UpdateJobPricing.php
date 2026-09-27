<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Contracts\SettingsStore;
use App\Modules\Jobs\Events\JobPricingChanged;
use App\Modules\Jobs\Services\JobPricing;
use App\Support\Money;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

/**
 * تغییر قیمت، مدت، «اولین آگهی رایگان»، مهلت ۴۱۰ و سقف درخواست روزانه از
 * پنل (DEC-63، DEC-66، DEC-74). دوره‌های پرداخت‌شده قیمت و مدت خودشان را نگه می‌دارند.
 */
final readonly class UpdateJobPricing
{
    public function __construct(
        private SettingsStore $settings,
        private JobPricing $pricing,
        private Dispatcher $events,
    ) {}

    public function handle(Money $price, int $days, bool $firstFree, int $goneAfterDays, int $applicationsPerDay, int $actorId): void
    {
        if ($price->isZero()) {
            throw new InvalidArgumentException('قیمت صفر یعنی انتشار رایگان برای همه؛ برای آن کلید «ثبت آگهی شغلی» را در درآمدزایی خاموش کنید.');
        }

        if ($days !== $this->pricing->clampDays($days)) {
            throw new InvalidArgumentException(sprintf('مدت اعتبار باید بین %d و %d روز باشد.', $this->pricing->daysMin(), $this->pricing->daysMax()));
        }

        if ($goneAfterDays < 1 || $goneAfterDays > 365) {
            throw new InvalidArgumentException('مهلت ماندن آگهی منقضی باید بین ۱ و ۳۶۵ روز باشد.');
        }

        if ($applicationsPerDay < 1 || $applicationsPerDay > $this->pricing->applicationsPerDayMax()) {
            throw new InvalidArgumentException(sprintf('سقف درخواست روزانه باید بین ۱ و %d باشد.', $this->pricing->applicationsPerDayMax()));
        }

        $before = $this->snapshot();
        $after = [
            JobPricing::PRICE_KEY => $price->toman,
            JobPricing::DAYS_KEY => $days,
            JobPricing::FIRST_FREE_KEY => $firstFree ? 1 : 0,
            JobPricing::GONE_KEY => $goneAfterDays,
            JobPricing::APPLY_LIMIT_KEY => $applicationsPerDay,
        ];

        if ($before === $after) {
            return;
        }

        $descriptions = [
            JobPricing::PRICE_KEY => 'قیمت انتشار هر آگهی شغلی (تومان)',
            JobPricing::DAYS_KEY => 'مدت اعتبار هر دوره انتشار آگهی (روز)',
            JobPricing::FIRST_FREE_KEY => 'اولین آگهی هر کارفرما رایگان است (۱ بله، ۰ خیر)',
            JobPricing::GONE_KEY => 'روزهای ماندن آگهی منقضی پیش از ۴۱۰',
            JobPricing::APPLY_LIMIT_KEY => 'سقف درخواست شغلی هر کارجو در ۲۴ ساعت',
        ];

        foreach ($after as $key => $value) {
            $this->settings->set($key, $value, JobPricing::GROUP, $descriptions[$key]);
        }

        $this->events->dispatch(new JobPricingChanged($before, $after, $actorId));
    }

    /** @return array<string, int> */
    public function snapshot(): array
    {
        return [
            JobPricing::PRICE_KEY => $this->pricing->price()->toman,
            JobPricing::DAYS_KEY => $this->pricing->days(),
            JobPricing::FIRST_FREE_KEY => $this->pricing->firstFree() ? 1 : 0,
            JobPricing::GONE_KEY => $this->pricing->goneAfterDays(),
            JobPricing::APPLY_LIMIT_KEY => $this->pricing->applicationsPerDay(),
        ];
    }
}
