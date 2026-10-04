<?php

declare(strict_types=1);

namespace App\Modules\Expert\Console;

use App\Modules\Expert\Actions\SyncEditorialQuestions;
use Illuminate\Console\Command;

/**
 * پرسش و پاسخ‌های نمونه تحریریه را می‌سازد یا به‌روز می‌کند.
 *
 * برخلاف محتوای نمونه دانشنامه، این محتوا برای سایت زنده است و در هر
 * استقرار اجرا می‌شود (`scripts/deploy.sh`). اجرای دوباره چیزی را تکرار
 * نمی‌کند.
 */
final class SyncEditorialQuestionsCommand extends Command
{
    protected $signature = 'fbh:expert:editorial';

    protected $description = 'همگام‌سازی پرسش و پاسخ‌های نمونه تحریریه در «پرسش از متخصص»';

    public function handle(SyncEditorialQuestions $sync): int
    {
        $result = $sync->handle();

        $this->info(sprintf('%d ردیف تازه، %d ردیف به‌روزشده.', $result['created'], $result['updated']));

        return self::SUCCESS;
    }
}
