<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Console;

use App\Modules\Chemicals\Actions\ApplyCsvImport;
use App\Modules\Chemicals\Domain\Import\ImportAction;
use App\Modules\Chemicals\Services\CsvImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

/**
 * ورود دستی یک فایل CSV، برای استقرار یا اصلاح دسته‌ای بدون پنل.
 *
 * همیشه اول گزارش تغییرات را چاپ می‌کند؛ فقط با `--apply` واقعاً می‌نویسد.
 * همان پیش‌نمایش/اجرای دوگانه‌ای که پنل مدیریت هم دارد.
 */
final class ImportSubstancesCommand extends Command
{
    protected $signature = 'fbh:import-substances {path : مسیر فایل CSV} {--apply : واقعاً بنویس، نه فقط پیش‌نمایش}';

    protected $description = 'گزارش تغییرات یک فایل CSV بانک مواد، و در صورت --apply اجرای آن';

    public function handle(CsvImporter $importer, ApplyCsvImport $apply): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("فایل پیدا نشد: {$path}");

            return self::FAILURE;
        }

        try {
            $plan = $importer->plan((string) file_get_contents($path));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['وضعیت', 'تعداد'], [
            ['ماده تازه', $plan->count(ImportAction::Create)],
            ['به‌روزرسانی', $plan->count(ImportAction::Update)],
            ['بدون تغییر', $plan->count(ImportAction::Unchanged)],
            ['نامعتبر', $plan->count(ImportAction::Invalid)],
        ]);

        foreach ($plan->of(ImportAction::Invalid) as $row) {
            $this->warn(sprintf('خط %d: %s', $row->line, $row->reason));
        }

        if (! $this->option('apply')) {
            $this->info('این فقط پیش‌نمایش بود. برای اجرا: --apply');

            return self::SUCCESS;
        }

        try {
            $apply->handle($plan);
            $this->info('اجرا شد.');

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
