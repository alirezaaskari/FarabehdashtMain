<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Services;

use App\Contracts\SalesSwitch;
use App\Contracts\SettingsStore;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\PostingPayment;
use App\Support\Money;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;

/**
 * قیمت و مدت انتشار آگهی (DEC-63، DEC-66).
 *
 * همه عددها تنظیم مدیر در پنل‌اند (قیمت، مدت، «اولین آگهی رایگان» و مهلت
 * ۴۱۰) و config فقط پیش‌فرض روز نصب است. کلید «ثبت آگهی شغلی» در
 * درآمدزایی که خاموش باشد، انتشار رایگان است: آگهی بخش اصلی کاریابی است و
 * بستنش همه کارفرماها را بیرون می‌گذارد.
 */
final readonly class JobPricing
{
    public const PRICE_KEY = 'jobs.posting_price_toman';

    public const DAYS_KEY = 'jobs.posting_days';

    public const FIRST_FREE_KEY = 'jobs.first_posting_free';

    public const GONE_KEY = 'jobs.expired_gone_days';

    public const GROUP = 'jobs';

    public function __construct(
        private Container $container,
        private Repository $config,
        private SalesSwitch $sales,
    ) {}

    public function price(): Money
    {
        return Money::toman(max(0, $this->setting(self::PRICE_KEY, (int) $this->config->get('jobs.pricing.price_toman', 350_000))));
    }

    public function days(): int
    {
        return $this->clampDays($this->setting(self::DAYS_KEY, (int) $this->config->get('jobs.pricing.days', 30)));
    }

    public function firstFree(): bool
    {
        return $this->setting(self::FIRST_FREE_KEY, (bool) $this->config->get('jobs.pricing.first_free', true) ? 1 : 0) === 1;
    }

    public function goneAfterDays(): int
    {
        return max(1, $this->setting(self::GONE_KEY, (int) $this->config->get('jobs.pricing.gone_after_days', 90)));
    }

    public function daysMin(): int
    {
        return (int) $this->config->get('jobs.pricing.days_min', 7);
    }

    public function daysMax(): int
    {
        return (int) $this->config->get('jobs.pricing.days_max', 120);
    }

    public function clampDays(int $days): int
    {
        return min($this->daysMax(), max($this->daysMin(), $days));
    }

    public function salesOpen(): bool
    {
        return $this->sales->isOpen(SalesSwitch::JOB_POSTING);
    }

    /** مبلغی که این کارفرما برای دوره بعدی انتشار می‌پردازد؛ صفر یعنی رایگان. */
    public function priceFor(Company $company): Money
    {
        if (! $this->salesOpen() || $this->price()->isZero()) {
            return Money::zero();
        }

        return $this->firstFree() && ! $this->hasPublished($company) ? Money::zero() : $this->price();
    }

    /** آیا کارفرما پیش‌تر دوره انتشاری (پولی یا رایگان) داشته است. */
    public function hasPublished(Company $company): bool
    {
        return PostingPayment::query()
            ->paid()
            ->whereHas('posting', static fn ($query) => $query->where('company_id', $company->id))
            ->exists();
    }

    private function setting(string $key, int $default): int
    {
        return $this->container->bound(SettingsStore::class)
            ? $this->container->make(SettingsStore::class)->integer($key, $default)
            : $default;
    }
}
