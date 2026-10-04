<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Console;

use App\Modules\Chemicals\Actions\SyncBundledSubstances;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * افزودن مواد داده اولیه که هنوز در بانک نیستند — در هر استقرار اجرا می‌شود.
 *
 * ماده موجود دست نمی‌خورد، پس ویرایش مدیر با استقرار بعدی برنمی‌گردد.
 * برخلاف دستورهای محتوای نمونه، در production هم کار می‌کند: این داده واقعی
 * و منبع‌دار است، نه نمونه.
 */
final class SyncChemicalsCommand extends Command
{
    protected $signature = 'fbh:sync-chemicals';

    protected $description = 'افزودن مواد داده اولیه بانک مواد شیمیایی که هنوز ثبت نشده‌اند';

    public function handle(SyncBundledSubstances $sync): int
    {
        try {
            $result = $sync->handle();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%d ماده تازه ساخته و منتشر شد؛ %d ماده از پیش بود و دست نخورد.',
            count($result->created),
            count($result->skipped),
        ));

        // ماده‌ای که منتشر نشد پیش‌نویس می‌ماند و در صف تأیید دیده می‌شود؛
        // استقرار به خاطر یک ردیف محتوا نمی‌ایستد.
        foreach ($result->failed as $cas => $reason) {
            $this->warn("{$cas}: {$reason}");
        }

        return self::SUCCESS;
    }
}
