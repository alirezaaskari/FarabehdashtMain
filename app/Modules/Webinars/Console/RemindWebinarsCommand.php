<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Console;

use App\Modules\Webinars\Actions\SendReminders;
use Illuminate\Console\Command;

final class RemindWebinarsCommand extends Command
{
    protected $signature = 'fbh:remind-webinars';

    protected $description = 'به ثبت‌نام‌شده‌های رویدادهایی که به‌زودی شروع می‌شوند یادآوری می‌کند.';

    public function handle(SendReminders $action): int
    {
        $this->components->info(sprintf('%d یادآور رویداد فرستاده شد.', $action->handle()));

        return self::SUCCESS;
    }
}
