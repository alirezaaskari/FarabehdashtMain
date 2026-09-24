<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Contracts\EntitlementGate;
use App\Models\User;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Support\Entitlement\Decision;
use App\Support\Entitlement\EntitlementReason;
use App\Support\Entitlement\Feature;
use App\Support\PersianDigits;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Route;

/**
 * پاسخ واقعی لایه دسترسی.
 *
 * ترتیب تصمیم، همان ترتیبی است که معیار پذیرش بخش ۱۴ لازم دارد:
 *
 *   ۱. کلید جریان خاموش است؟ اجازه بده — برای همه، بدون سقف.
 *   ۲. کاربر مشترک است (خودش یا با صندلی)؟ اجازه بده — بدون سقف.
 *   ۳. سقف رایگانی هست؟ بشمار و تصمیم بگیر.
 *   ۴. سقف رایگانی نیست؟ این امکان فقط برای مشترک است.
 *
 * قدم اول پیش از همه می‌آید تا خاموش‌کردن اشتراک، رفتار کل سایت را بدون یک
 * خط تغییر در صفحه‌ها عوض کند.
 */
final readonly class EntitlementResolver implements EntitlementGate
{
    public function __construct(
        private Config $config,
        private StreamRegistry $streams,
        private SubscriptionReader $subscriptions,
        private QuotaTally $tally,
    ) {}

    public function decide(?User $user, Feature $feature): Decision
    {
        $stream = $this->streamFor($feature);

        if ($stream === null || ! $this->streams->isEnabled($stream)) {
            return Decision::allow(EntitlementReason::StreamDisabled);
        }

        if ($user === null) {
            return Decision::deny(
                EntitlementReason::SignInRequired,
                sprintf('برای «%s» اول وارد حساب خود شوید.', $feature->label()),
                upgradeUrl: $this->loginUrl(),
            );
        }

        if ($this->subscriptions->hasAccess($user)) {
            return Decision::allow(EntitlementReason::Subscribed);
        }

        $limit = $this->freeLimitFor($feature);

        if ($limit === null) {
            return Decision::deny(
                EntitlementReason::RequiresPro,
                sprintf('«%s» با اشتراک حرفه‌ای در دسترس است.', $feature->label()),
                upgradeUrl: $this->upgradeUrl($feature),
            );
        }

        $used = $this->tally->countFor($user, $feature);

        // هیچ شمارنده‌ای ثبت نشده یعنی ماژول صاحب داده خاموش است؛ چیزی که
        // شمرده نمی‌شود، سقف هم نمی‌خورد.
        if ($used === null || $used < $limit) {
            return Decision::allow(EntitlementReason::WithinFreeQuota, $used, $limit);
        }

        return Decision::deny(
            EntitlementReason::QuotaExhausted,
            sprintf(
                'سقف رایگان «%s» پر شده است: %s از %s.',
                $feature->label(),
                PersianDigits::from($used),
                PersianDigits::from($limit),
            ),
            $used,
            $limit,
            $this->upgradeUrl($feature),
        );
    }

    private function streamFor(Feature $feature): ?RevenueStream
    {
        $value = $this->config->get('monetization.features.'.$feature->value);

        return is_string($value) ? RevenueStream::tryFrom($value) : null;
    }

    private function freeLimitFor(Feature $feature): ?int
    {
        $limit = $this->config->get('monetization.free_limits.'.$feature->value);

        return is_int($limit) ? $limit : null;
    }

    /**
     * مقصد گذرگاه تبدیل، با شناسه امکانی که رد شد.
     *
     * گذرگاه باید بداند چه چیزی رد شد تا متنش مخصوص همان باشد؛ «۵ محاسبه
     * رایگان پر شده» با «پروژه برای مشترکان است» یک صفحه نیستند.
     */
    private function upgradeUrl(Feature $feature): ?string
    {
        return Route::has('monetization.upgrade')
            ? route('monetization.upgrade', ['feature' => $feature->value])
            : null;
    }

    private function loginUrl(): ?string
    {
        return Route::has('login') ? route('login') : null;
    }
}
