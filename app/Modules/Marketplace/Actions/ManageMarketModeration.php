<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Contracts\SettingsStore;
use App\Modules\Marketplace\Domain\Enums\ReviewMode;
use App\Modules\Marketplace\Domain\MarketStrike;
use App\Modules\Marketplace\Events\MarketAccessReopened;
use App\Modules\Marketplace\Events\MessagePolicyChanged;
use App\Modules\Marketplace\Services\MessagePolicy;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * کلید حالت بررسی پیام و فهرست کلمه‌ها (DEC-80)، و باز کردن دوباره دسترسی
 * کاربری که به سقف اخطار رسیده.
 *
 * عوض‌کردن حالت فقط روی پیام‌های بعدی اثر دارد؛ پیام‌های در صف همان‌جا می‌مانند.
 */
final readonly class ManageMarketModeration
{
    public function __construct(
        private SettingsStore $settings,
        private MessagePolicy $policy,
        private Dispatcher $events,
    ) {}

    /** @param  list<string>  $words */
    public function save(ReviewMode $mode, array $words, int $adminId): void
    {
        $words = array_values(array_unique(array_filter(array_map(static fn (string $word): string => trim($word), $words))));

        if ($words === []) {
            throw new RuntimeException('فهرست کلمه‌ها خالی نمی‌ماند؛ دست‌کم نام پیام‌رسان‌ها بماند.');
        }

        $before = $this->policy->mode();

        $this->settings->set(MessagePolicy::MODE_KEY, $mode->value, 'marketplace', 'حالت بررسی پیام بازار پروژه');
        $this->settings->set(MessagePolicy::WORDS_KEY, implode("\n", $words), 'marketplace', 'کلمه‌های مشکوک پیام بازار پروژه');

        $this->events->dispatch(new MessagePolicyChanged($before, $mode, count($words), $adminId));
    }

    public function reopen(int $userId, int $adminId): void
    {
        $cleared = MarketStrike::query()
            ->where('user_id', $userId)
            ->whereNull('cleared_at')
            ->update(['cleared_at' => Carbon::now(), 'cleared_by' => $adminId]);

        if ($cleared === 0) {
            throw new RuntimeException('این کاربر اخطار فعالی ندارد.');
        }

        $this->events->dispatch(new MarketAccessReopened($userId, $adminId));
    }
}
