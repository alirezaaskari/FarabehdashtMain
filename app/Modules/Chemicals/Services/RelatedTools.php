<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Services;

use App\Contracts\ToolDirectory;
use App\Support\Tools\ToolSummary;
use Illuminate\Contracts\Container\Container;

/**
 * ابزارهای عمومی مرتبط با بانک مواد — تبدیل واحد، محاسبه TWA.
 *
 * برخلاف بلوک ابزار دانشنامه که به یک بخش خاص از یک محتوا گره می‌خورد، این
 * فهرست به همه مواد یکسان مرتبط است: تبدیل ppm↔mg/m³ برای هر ماده معنا دارد.
 *
 * اگر ماژول ابزارها خاموش باشد، قرارداد بسته نشده و فهرست خالی برمی‌گردد —
 * صفحه ماده بدون بخش «محاسبه با این ماده» نمایش داده می‌شود.
 */
final readonly class RelatedTools
{
    /** @param  list<string>  $slugs */
    public function __construct(
        private Container $container,
        private array $slugs,
    ) {}

    /** @return list<ToolSummary> */
    public function all(): array
    {
        if (! $this->container->bound(ToolDirectory::class)) {
            return [];
        }

        $directory = $this->container->make(ToolDirectory::class);
        $tools = [];

        foreach ($this->slugs as $slug) {
            $tool = $directory->find($slug);

            if ($tool !== null) {
                $tools[] = $tool;
            }
        }

        return $tools;
    }
}
