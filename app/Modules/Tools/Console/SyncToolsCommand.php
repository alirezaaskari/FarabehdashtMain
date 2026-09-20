<?php

declare(strict_types=1);

namespace App\Modules\Tools\Console;

use App\Modules\Tools\Domain\Tool;
use App\Modules\Tools\Services\ToolCatalog;
use Illuminate\Console\Command;

/**
 * ساخت ردیف وضعیت برای ابزارهایی که در کد هستند و هنوز در جدول نیستند.
 *
 * ردیف موجود دست نمی‌خورد: مدیری که ابزاری را خاموش کرده، نباید با استقرار
 * بعدی دوباره روشنش ببیند.
 *
 * ردیف یتیم (ابزاری که از کد حذف شده) هم پاک نمی‌شود و فقط گزارش می‌شود؛
 * حذف خودکار یعنی یک اشتباه تایپی در پیکربندی، تاریخچه بازبینی را ببرد.
 */
final class SyncToolsCommand extends Command
{
    protected $signature = 'fbh:sync-tools';

    protected $description = 'همگام‌سازی فهرست ابزارها با جدول وضعیت';

    public function handle(ToolCatalog $catalog): int
    {
        $defined = array_keys($catalog->definitions());

        /** @var list<string> $existing */
        $existing = Tool::query()->pluck('slug')->all();

        $created = 0;

        foreach (array_diff($defined, $existing) as $slug) {
            Tool::query()->create(['slug' => $slug, 'is_enabled' => true]);
            $created++;
        }

        $catalog->forget();

        $this->info(sprintf('%d ابزار تازه ثبت شد.', $created));

        $orphans = array_diff($existing, $defined);

        if ($orphans !== []) {
            $this->warn(sprintf(
                'این ردیف‌ها در کد تعریفی ندارند و دست‌نخورده ماندند: %s',
                implode('، ', $orphans),
            ));
        }

        return self::SUCCESS;
    }
}
