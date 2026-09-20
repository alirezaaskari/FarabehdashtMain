<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Contracts\ToolDirectory;

/**
 * پیاده‌سازی قرارداد `ToolDirectory` برای ماژول‌های دیگر.
 *
 * تنها چیزی که بیرون می‌دهد یک رشته است: عنوان فارسی ابزار. هیچ بخشی از
 * `ToolDefinition` از این در عبور نمی‌کند (قاعده ۱).
 */
final readonly class ToolTitleDirectory implements ToolDirectory
{
    public function __construct(private ToolCatalog $catalog) {}

    public function titleFor(string $slug): ?string
    {
        if (! $this->catalog->has($slug)) {
            return null;
        }

        return $this->catalog->resolve($slug)->definition->title;
    }
}
