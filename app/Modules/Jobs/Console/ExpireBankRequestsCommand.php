<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Console;

use App\Modules\Jobs\Actions\ResumeBankRequests;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * درخواست تماسی که در مهلت پاسخ نگرفت «بی‌پاسخ» می‌شود و اعتبارش به
 * کارفرما برمی‌گردد (DEC-72).
 */
#[AsCommand(name: 'jobs:bank-expire', description: 'بستن درخواست‌های تماس بی‌پاسخ بانک رزومه و برگرداندن اعتبار')]
final class ExpireBankRequestsCommand extends Command
{
    public function handle(ResumeBankRequests $requests): int
    {
        $this->info('بسته شد: '.$requests->expireDue());

        return self::SUCCESS;
    }
}
