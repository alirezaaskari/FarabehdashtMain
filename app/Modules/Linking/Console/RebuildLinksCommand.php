<?php

declare(strict_types=1);

namespace App\Modules\Linking\Console;

use App\Modules\Linking\Actions\RebuildLinks;
use Illuminate\Console\Command;

/**
 * بازسازی پیوندهای داخلی. `scripts/deploy.sh` پس از هر استقرار اجرایش می‌کند؛
 * پس از ورود دسته‌ای مواد یا محتوا هم می‌شود دستی زد.
 */
final class RebuildLinksCommand extends Command
{
    protected $signature = 'fbh:links:rebuild';

    protected $description = 'بازسازی پیوندهای داخلی خودکار از روی متن فعلی دانشنامه';

    public function handle(RebuildLinks $rebuild): int
    {
        $result = $rebuild->handle();

        $this->components->info(sprintf(
            '%d مقصد، %d سند، %d پیوند.',
            $result['targets'],
            $result['documents'],
            $result['links'],
        ));

        return self::SUCCESS;
    }
}
