<?php

declare(strict_types=1);

namespace App\Modules\Projects\Console;

use App\Modules\Projects\Actions\RemindCalibrations;
use Illuminate\Console\Command;

/**
 * یادآور پایان کالیبراسیون تجهیزات — روزی یک‌بار، بیرون از ساعت سکوت پیامک.
 */
final class RemindCalibrationsCommand extends Command
{
    protected $signature = 'fbh:remind-calibrations';

    protected $description = 'به صاحبان تجهیزاتی که کالیبراسیونشان به‌زودی تمام می‌شود یادآوری می‌کند.';

    public function handle(RemindCalibrations $action): int
    {
        $this->components->info(sprintf('%d کاربر یادآور کالیبراسیون گرفتند.', $action->handle()));

        return self::SUCCESS;
    }
}
