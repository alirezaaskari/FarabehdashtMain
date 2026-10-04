<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Console;

use App\Models\User;
use App\Modules\Encyclopedia\Services\StarterArticles;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * نصب مقاله‌های آغازین دانشنامه — برخلاف fbh:seed-encyclopedia، برای سایت زنده.
 *
 * بدون گزینه، مقاله‌های تازه را «در انتظار بازبینی» می‌سازد و `deploy.sh` همین را
 * صدا می‌زند. با `--publish`، آن‌هایی که هنوز در انتظارند با بازبین داده‌شده
 * منتشر می‌شوند.
 */
final class StarterArticlesCommand extends Command
{
    protected $signature = 'fbh:starter-articles
        {--publish : مقاله‌های در انتظار بازبینی را منتشر کن}
        {--reviewer= : موبایل بازبین علمی، برای --publish}';

    protected $description = 'نصب مقاله‌های آغازین دانشنامه (اجرای دوباره بی‌خطر است)';

    public function handle(StarterArticles $starter): int
    {
        $this->info(sprintf('%d مقاله آغازین تازه در صف بازبینی نشست.', $starter->install()));

        if (! $this->option('publish')) {
            return self::SUCCESS;
        }

        $mobile = $this->option('reviewer');
        $reviewer = is_string($mobile) ? User::query()->where('mobile', $mobile)->first() : null;

        if ($reviewer === null) {
            $this->error('برای انتشار، موبایل یک حساب موجود را با --reviewer بدهید.');

            return self::FAILURE;
        }

        try {
            $this->info(sprintf('%d مقاله آغازین منتشر شد.', $starter->publish($reviewer)));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
