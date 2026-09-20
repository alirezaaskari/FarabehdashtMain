<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Contracts\ToolDirectory;
use App\Support\Tools\ToolSummary;

/**
 * پیاده‌سازی قرارداد `ToolDirectory` برای ماژول‌های دیگر.
 *
 * تنها چیزی که بیرون می‌دهد یک `ToolSummary` است — پنج رشته. هیچ بخشی از
 * `ToolDefinition` یا `Formula` از این در عبور نمی‌کند (قاعده ۱).
 */
final readonly class ToolTitleDirectory implements ToolDirectory
{
    public function __construct(private ToolCatalog $catalog) {}

    public function find(string $slug): ?ToolSummary
    {
        if (! $this->catalog->has($slug)) {
            return null;
        }

        $tool = $this->catalog->resolve($slug);

        return new ToolSummary(
            slug: $tool->slug(),
            title: $tool->definition->title,
            summary: $tool->definition->summary,
            reference: $tool->formula->reference()->title,
            version: $tool->version(),
        );
    }
}
