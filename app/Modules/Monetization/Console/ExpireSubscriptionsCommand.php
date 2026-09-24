<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Console;

use App\Modules\Monetization\Actions\ExpireSubscriptions;
use Illuminate\Console\Command;

/**
 * هم‌ترازکردن وضعیت اشتراک‌های گذشته — روزی یک‌بار با cron.
 */
final class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'fbh:expire-subscriptions';

    protected $description = 'اشتراک‌هایی را که تاریخشان گذشته «منقضی» علامت می‌زند.';

    public function handle(ExpireSubscriptions $action): int
    {
        $count = $action->handle();

        $this->components->info(sprintf('%d اشتراک منقضی شد.', $count));

        return self::SUCCESS;
    }
}
