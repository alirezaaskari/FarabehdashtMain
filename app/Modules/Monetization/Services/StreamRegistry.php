<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Enums\ShutdownPolicy;
use App\Modules\Monetization\Domain\RevenueStreamToggle;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Schema;

/**
 * وضعیت کلیدهای درآمدزایی.
 *
 * جدول فقط تغییرهای مدیر را نگه می‌دارد؛ جریانی که ردیف ندارد پیش‌فرض
 * `config/monetization.php` را می‌گیرد. پس نصب تازه بدون seed کار می‌کند و
 * جدول خالی یعنی «هنوز کسی چیزی را عوض نکرده»، نه «همه‌چیز خاموش».
 *
 * پاسخ کش می‌شود چون تقریباً هر درخواستِ لایه دسترسی به آن نیاز دارد؛ هر
 * تغییر کلید، کش را باطل می‌کند.
 *
 * نبودِ جدول هم یک حالت واقعی است، نه خطا: استقرار «git pull سپس migrate»
 * است، پس بین آن دو قدم جدول هنوز نیست و پوسته سایت — که ردیف «اشتراک» را
 * از همین‌جا می‌پرسد — نباید کل سایت را ۵۰۰ کند. همان استدلالی که
 * `SettingsRepository` را وادار کرد `default` را جدی بگیرد.
 */
final readonly class StreamRegistry
{
    private const CACHE_KEY = 'monetization.streams';

    public function __construct(
        private Config $config,
        private Cache $cache,
    ) {}

    public function isEnabled(RevenueStream $stream): bool
    {
        return $this->state($stream)['enabled'];
    }

    public function policyFor(RevenueStream $stream): ShutdownPolicy
    {
        return ShutdownPolicy::from($this->state($stream)['policy']);
    }

    /**
     * وضعیت همه جریان‌ها — برای صفحه کلیدهای مدیر.
     *
     * @return array<string, array{enabled: bool, policy: string}>
     */
    public function all(): array
    {
        /** @var array<string, array{enabled: bool, policy: string}> $stored */
        $stored = $this->cache->rememberForever(self::CACHE_KEY, static function (): array {
            if (! Schema::hasTable('revenue_streams')) {
                return [];
            }

            $rows = [];

            foreach (RevenueStreamToggle::query()->get() as $toggle) {
                $rows[$toggle->stream->value] = [
                    'enabled' => $toggle->is_enabled,
                    'policy' => $toggle->shutdown_policy->value,
                ];
            }

            return $rows;
        });

        $states = [];

        foreach (RevenueStream::cases() as $stream) {
            $states[$stream->value] = $stored[$stream->value] ?? [
                'enabled' => $this->defaultFor($stream),
                'policy' => ShutdownPolicy::RunToEnd->value,
            ];
        }

        return $states;
    }

    public function forget(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }

    /** @return array{enabled: bool, policy: string} */
    private function state(RevenueStream $stream): array
    {
        return $this->all()[$stream->value];
    }

    private function defaultFor(RevenueStream $stream): bool
    {
        // جریانی که ماژولش ساخته نشده هرگز روشن نیست، هر چه پیکربندی بگوید:
        // کلید روشنی که هیچ کاری نمی‌کند، وعده دروغ به مدیر است.
        if (! $stream->isBuilt()) {
            return false;
        }

        return (bool) $this->config->get('monetization.streams.'.$stream->value, false);
    }
}
