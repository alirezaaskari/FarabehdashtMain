<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Console;

use App\Modules\Jobs\Domain\JobAlert;
use App\Modules\Jobs\Domain\JobAlertMatch;
use App\Modules\Jobs\Events\JobAlertDigest;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * خلاصه روزانه هشدار شغل (DEC-73): برای هر کسی که پیامک هشدار را روشن کرده
 * و از خلاصه قبلی آگهی تازه دارد، یک اعلان که ماژول اعلان‌ها پیامکش می‌کند.
 * همه تطبیق‌های فرستاده‌شده «خلاصه‌شده» علامت می‌خورند تا تکرار نشوند.
 */
#[AsCommand(name: 'jobs:alert-digest', description: 'خلاصه روزانه هشدار شغل برای کسانی که پیامک هشدار را روشن کرده‌اند')]
final class SendJobAlertDigestsCommand extends Command
{
    public function handle(Dispatcher $events): int
    {
        $now = Carbon::now();
        $smsUsers = JobAlert::query()->where('sms', true)->distinct()->pluck('user_id')->all();

        $counts = JobAlertMatch::query()
            ->whereNull('digested_at')
            ->whereIn('user_id', $smsUsers)
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        foreach ($counts as $userId => $total) {
            $events->dispatch(new JobAlertDigest((int) $userId, (int) $total));
        }

        // تطبیق کسانی که پیامک ندارند هم بسته می‌شود تا روشن‌کردن بعدی پیامک، انبوه قدیمی نفرستد.
        JobAlertMatch::query()->whereNull('digested_at')->where('created_at', '<=', $now)->update(['digested_at' => $now]);

        $this->info(count($counts).' خلاصه ساخته شد.');

        return self::SUCCESS;
    }
}
