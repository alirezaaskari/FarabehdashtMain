<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Enums\ShutdownPolicy;
use App\Modules\Monetization\Domain\RevenueStreamToggle;
use App\Modules\Monetization\Events\RevenueStreamToggled;
use App\Modules\Monetization\Services\ShutdownPreview;
use App\Modules\Monetization\Services\StreamRegistry;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * روشن یا خاموش‌کردن یک جریان درآمدی.
 *
 * دو قاعده ثابت سند کلیدها این‌جا اجرا می‌شوند: هیچ داده‌ای حذف نمی‌شود، و
 * هر تغییر یک ردیف دفتر رویداد می‌سازد.
 *
 * سیاست «قطع فوری بدون بازگشت وجه» فقط وقتی مجاز است که هیچ مشترک فعالی
 * نباشد. این بررسی در اکشن است نه در صفحه، چون قاعده‌ای است که نباید با
 * عوض‌شدن رابط کاربری قابل دورزدن باشد.
 */
final readonly class ToggleRevenueStream
{
    public function __construct(
        private StreamRegistry $streams,
        private ShutdownPreview $preview,
        private Dispatcher $events,
    ) {}

    public function handle(
        RevenueStream $stream,
        bool $enabled,
        ShutdownPolicy $policy = ShutdownPolicy::RunToEnd,
        ?int $actorId = null,
    ): void {
        if (! $stream->isBuilt() && $enabled) {
            throw new RuntimeException(sprintf('جریان «%s» هنوز ساخته نشده و روشن نمی‌شود.', $stream->label()));
        }

        $wasEnabled = $this->streams->isEnabled($stream);

        if (! $enabled && $policy->forbidsActiveSubscribers() && $this->preview->for($stream)->activeSubscribers > 0) {
            throw new RuntimeException('قطع فوری بدون بازگشت وجه، با وجود مشترک فعال مجاز نیست.');
        }

        RevenueStreamToggle::query()->updateOrCreate(
            ['stream' => $stream->value],
            [
                'is_enabled' => $enabled,
                'shutdown_policy' => $policy,
                'changed_at' => Carbon::now(),
            ],
        );

        $this->streams->forget();

        $this->events->dispatch(new RevenueStreamToggled($stream, $wasEnabled, $enabled, $policy, $actorId));
    }
}
