<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Console;

use App\Modules\Monetization\Actions\RemindEndingSubscriptions;
use Illuminate\Console\Command;

/**
 * یادآور پایان اشتراک — روزی یک‌بار، بیرون از ساعت سکوت پیامک.
 */
final class RemindEndingSubscriptionsCommand extends Command
{
    protected $signature = 'fbh:remind-subscription-endings';

    protected $description = 'به مشترکانی که اشتراکشان به‌زودی تمام می‌شود یادآوری می‌کند.';

    public function handle(RemindEndingSubscriptions $action): int
    {
        $this->components->info(sprintf('%d یادآور پایان اشتراک رفت.', $action->handle()));

        return self::SUCCESS;
    }
}
