<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Modules\Tools\Domain\ResolvedTool;

/**
 * دستیار انتخاب ابزار.
 *
 * نه هوش مصنوعی و نه درخت تصمیم: فهرستی از موقعیت‌های واقعی میدانی، هر کدام
 * با یک ابزار و یک دلیل. کسی که اسم ابزار را نمی‌داند، موقعیت خودش را
 * می‌شناسد — «چند دستگاه هم‌زمان کار می‌کنند» را می‌فهمد، «جمع لگاریتمی» را نه.
 *
 * موقعیتی که ابزارش غیرفعال یا تعریف‌نشده باشد، نمایش داده نمی‌شود؛ پیشنهاد
 * ابزاری که باز نمی‌شود بدتر از نبودِ پیشنهاد است.
 */
final readonly class ToolAdvisor
{
    /**
     * @param  list<array<string, string>>  $situations
     */
    public function __construct(
        private ToolCatalog $catalog,
        private array $situations,
    ) {}

    /**
     * @return list<array{situation: string, reason: string, tool: ResolvedTool}>
     */
    public function suggestions(): array
    {
        $suggestions = [];

        foreach ($this->situations as $entry) {
            $slug = $entry['tool'] ?? '';

            if (! $this->catalog->has($slug)) {
                continue;
            }

            $tool = $this->catalog->resolve($slug);

            if (! $tool->usable()) {
                continue;
            }

            $suggestions[] = [
                'situation' => $entry['situation'] ?? '',
                'reason' => $entry['reason'] ?? '',
                'tool' => $tool,
            ];
        }

        return $suggestions;
    }
}
