<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Console;

use App\Modules\Workspace\Actions\SendDueNoticeSms;
use Illuminate\Console\Command;

/**
 * صف پیامک اعلان را خالی می‌کند — هر دقیقه با `schedule:run`.
 */
final class SendNoticeSmsCommand extends Command
{
    protected $signature = 'fbh:send-notice-sms';

    protected $description = 'پیامک اعلان‌های مهمی را که موعدشان رسیده می‌فرستد.';

    public function handle(SendDueNoticeSms $action): int
    {
        $this->components->info(sprintf('%d پیامک اعلان فرستاده شد.', $action->handle()));

        return self::SUCCESS;
    }
}
