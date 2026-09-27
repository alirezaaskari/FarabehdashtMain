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
 * قیمت، مدت و سقف‌های کاریابی (DEC-63، DEC-66، DEC-70، DEC-72، DEC-74).
 *
 * همه عددها تنظیم مدیر در پنل‌اند ({@see rules()}) و config فقط پیش‌فرض
 * روز نصب است. کلید «ثبت آگهی شغلی» در
 * درآمدزایی که خاموش باشد، انتشار رایگان است: آگهی بخش اصلی کاریابی است و
 * بستنش همه کارفرماها را بیرون می‌گذارد.
 */
final readonly class JobPricing
{
    public const PRICE_KEY = 'jobs.posting_price_toman';

    public const DAYS_KEY = 'jobs.posting_days';

    public const FIRST_FREE_KEY = 'jobs.first_posting_free';

    public const GONE_KEY = 'jobs.expired_gone_days';

    public const APPLY_LIMIT_KEY = 'jobs.applications_per_day';

    public const EXAM_MIN_KEY = 'jobs.passport_exam_min_percent';

    public const BANK_PRICE_KEY = 'jobs.resume_bank_price_toman';

    public const BANK_CREDITS_KEY = 'jobs.resume_bank_credits';

    public const BANK_REPLY_DAYS_KEY = 'jobs.resume_bank_reply_days';

    public const GROUP = 'jobs';

    public function __construct(
        private Container $container,
        private Repository $config,
        private SalesSwitch $sales,
    ) {}

    /**
     * همه عددهای کاریابی که مدیر از پنل عوض می‌کند، با نام کوتاه فرم.
     * `default` از config می‌آید و `min`/`max` مرز پذیرفتنی است.
     *
     * @return array<string, array{key: string, label: string, min: int, max: int, default: int}>
     */
    public function rules(): array
    {
        $config = fn (string $path, int $fallback): int => (int) $this->config->get($path, $fallback);

        return [
            'price' => ['key' => self::PRICE_KEY, 'label' => 'قیمت انتشار هر آگهی شغلی (تومان)', 'min' => 1, 'max' => 100_000_000, 'default' => $config('jobs.pricing.price_toman', 350_000)],
            'days' => ['key' => self::DAYS_KEY, 'label' => 'مدت اعتبار هر دوره انتشار آگهی (روز)', 'min' => $this->daysMin(), 'max' => $this->daysMax(), 'default' => $config('jobs.pricing.days', 30)],
            'first_free' => ['key' => self::FIRST_FREE_KEY, 'label' => 'اولین آگهی هر کارفرما رایگان است (۱ بله، ۰ خیر)', 'min' => 0, 'max' => 1, 'default' => (bool) $this->config->get('jobs.pricing.first_free', true) ? 1 : 0],
            'gone_days' => ['key' => self::GONE_KEY, 'label' => 'روزهای ماندن آگهی منقضی پیش از ۴۱۰', 'min' => 1, 'max' => 365, 'default' => $config('jobs.pricing.gone_after_days', 90)],
            'apply_limit' => ['key' => self::APPLY_LIMIT_KEY, 'label' => 'سقف درخواست شغلی هر کارجو در ۲۴ ساعت', 'min' => 1, 'max' => $config('jobs.applications.per_day_max', 200), 'default' => $config('jobs.applications.per_day', 20)],
            'exam_min' => ['key' => self::EXAM_MIN_KEY, 'label' => 'کمینه نمره آزمون زمان‌دار برای گذرنامه (درصد)', 'min' => 1, 'max' => 100, 'default' => $config('jobs.passport.exam_min_percent', 70)],
            'bank_price' => ['key' => self::BANK_PRICE_KEY, 'label' => 'قیمت هر بسته درخواست تماس بانک رزومه (تومان)', 'min' => 1, 'max' => 100_000_000, 'default' => $config('jobs.bank.price_toman', 490_000)],
            'bank_credits' => ['key' => self::BANK_CREDITS_KEY, 'label' => 'تعداد درخواست تماس در هر بسته', 'min' => 1, 'max' => 500, 'default' => $config('jobs.bank.credits', 10)],
            'bank_reply_days' => ['key' => self::BANK_REPLY_DAYS_KEY, 'label' => 'مهلت پاسخ کارجو به درخواست تماس (روز)', 'min' => 1, 'max' => 60, 'default' => $config('jobs.bank.reply_days', 7)],
        ];
    }

    /** مقدار فعلی یک قاعده، در مرز min و max. */
    public function value(string $name): int
    {
        $rule = $this->rules()[$name];

        return min($rule['max'], max($rule['min'], $this->setting($rule['key'], $rule['default'])));
    }

    public function price(): Money
    {
        return Money::toman($this->value('price'));
    }

    public function days(): int
    {
        return $this->value('days');
    }

    public function firstFree(): bool
    {
        return $this->value('first_free') === 1;
    }

    public function goneAfterDays(): int
    {
        return $this->value('gone_days');
    }

    /** سقف درخواست هر کارجو در ۲۴ ساعت (DEC-74)؛ ضد ارسال انبوه، نه هزینه. */
    public function applicationsPerDay(): int
    {
        return $this->value('apply_limit');
    }

    /** کمینه درصد نمره آزمون زمان‌دار که در گذرنامه می‌آید (DEC-70). */
    public function examMinPercent(): int
    {
        return $this->value('exam_min');
    }

    public function bankPrice(): Money
    {
        return Money::toman($this->value('bank_price'));
    }

    public function bankCredits(): int
    {
        return $this->value('bank_credits');
    }

    public function bankReplyDays(): int
    {
        return $this->value('bank_reply_days');
    }

    /** کلید «دسترسی کارفرما به بانک رزومه» خاموش یعنی درخواست تماس بی‌اعتبار. */
    public function bankCharging(): bool
    {
        return $this->sales->isOpen(SalesSwitch::RESUME_BANK);
    }

    public function daysMin(): int
    {
        return (int) $this->config->get('jobs.pricing.days_min', 7);
    }

    public function daysMax(): int
    {
        return (int) $this->config->get('jobs.pricing.days_max', 120);
    }

    public function salesOpen(): bool
    {
        return $this->sales->isOpen(SalesSwitch::JOB_POSTING);
    }

    /** مبلغی که این کارفرما برای دوره بعدی انتشار می‌پردازد؛ صفر یعنی رایگان. */
    public function priceFor(Company $company): Money
    {
        if (! $this->salesOpen()) {
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
