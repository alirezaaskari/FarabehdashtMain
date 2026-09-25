<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Modules\Monetization\Domain\Enums\BillingCycle;
use App\Modules\Monetization\Domain\Plan;
use App\Support\Entitlement\Feature;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;

/**
 * پلن‌های فروش، با بذر روز نصب.
 *
 * جدول خالی یعنی هنوز نصب تازه است، نه «هیچ پلنی وجود ندارد»: اولین خواندن،
 * پلن‌های `config/monetization.php` را می‌نویسد. از آن پس ردیف دیتابیس
 * مبناست و تغییر قیمت در پیکربندی هیچ پلنی را جابه‌جا نمی‌کند — وگرنه یک
 * `composer update` می‌توانست قیمت فروش را عوض کند.
 */
final readonly class PlanCatalog
{
    public function __construct(private Config $config) {}

    /** @return Collection<int, Plan> */
    public function active(): Collection
    {
        $this->seedOnce();

        return Plan::query()->active()->get();
    }

    public function findBySlug(string $slug): ?Plan
    {
        $this->seedOnce();

        return Plan::query()->where('slug', $slug)->where('is_active', true)->first();
    }

    /**
     * چند ماه از سال با پلن سالانه رایگان درمی‌آید، در برابر دوازده ماه ماهانه.
     *
     * صفر وقتی یکی از دو پلن نیست یا سالانه ارزان‌تر نیست؛ صفحه پلن‌ها آن‌وقت
     * وعده صرفه‌جویی نمی‌دهد.
     *
     * @param  Collection<int, Plan>  $plans
     */
    public function yearlyFreeMonths(Collection $plans): int
    {
        $monthly = $plans->first(static fn (Plan $plan): bool => $plan->billing_cycle === BillingCycle::Monthly);
        $yearly = $plans->first(static fn (Plan $plan): bool => $plan->billing_cycle === BillingCycle::Yearly);

        if ($monthly === null || $yearly === null || $monthly->price_toman <= 0) {
            return 0;
        }

        return max(0, intdiv($monthly->price_toman * 12 - $yearly->price_toman, $monthly->price_toman));
    }

    /**
     * سهم پلن رایگان از یک امکان: عدد برای امکان سقف‌دار، null یعنی اصلاً ندارد.
     *
     * همان پیکربندی‌ای که لایه دسترسی اجرا می‌کند؛ صفحه‌ها عددی جز این وعده نمی‌دهند.
     */
    public function freeAllowance(Feature $feature): ?int
    {
        $limit = $this->config->get('monetization.free_limits.'.$feature->value);

        return is_numeric($limit) ? (int) $limit : null;
    }

    private function seedOnce(): void
    {
        if (Plan::query()->exists()) {
            return;
        }

        /** @var list<array{slug: string, title: string, cycle: string, price_toman: int}> $plans */
        $plans = $this->config->get('monetization.plans', []);

        foreach ($plans as $index => $plan) {
            Plan::query()->create([
                'slug' => $plan['slug'],
                'title' => $plan['title'],
                'billing_cycle' => BillingCycle::from($plan['cycle']),
                'price_toman' => $plan['price_toman'],
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }
}
