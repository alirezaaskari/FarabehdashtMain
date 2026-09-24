<?php

declare(strict_types=1);

namespace App\Modules\Tools\Linking;

use App\Contracts\LinkTargetSource;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\Linking\LinkTarget;
use Illuminate\Support\Facades\Route;

/**
 * ابزارهای قابل استفاده به‌عنوان مقصد پیوند، با عنوانشان.
 */
final readonly class ToolLinks implements LinkTargetSource
{
    public function __construct(private ToolCatalog $catalog) {}

    public static function key(string $slug): string
    {
        return 'tools:'.$slug;
    }

    /** @return iterable<LinkTarget> */
    public function linkTargets(): iterable
    {
        if (! Route::has('tools.show')) {
            return;
        }

        foreach ($this->catalog->usable() as $tool) {
            $title = $tool->definition->title;

            yield new LinkTarget(self::key($tool->slug()), $title, route('tools.show', $tool->slug()), LinkTarget::titlePhrases($title));
        }
    }
}
