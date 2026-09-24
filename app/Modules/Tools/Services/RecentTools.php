<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Domain\SavedCalculation;

/**
 * ابزارهایی که کاربر اخیراً با آن‌ها محاسبه ذخیره کرده است.
 *
 * کارشناس هر روز همان دو سه ابزار را باز می‌کند؛ این فهرست از داده خود سایت
 * ساخته می‌شود و چیزی بیرون نمی‌رود.
 */
final readonly class RecentTools
{
    private const LIMIT = 4;

    public function __construct(private ToolCatalog $catalog) {}

    /**
     * @return list<ResolvedTool>
     */
    public function for(int $userId): array
    {
        $slugs = SavedCalculation::query()
            ->forUser($userId)
            ->selectRaw('tool_slug, MAX(id) AS last_id')
            ->groupBy('tool_slug')
            ->orderByDesc('last_id')
            ->limit(self::LIMIT * 2)
            ->pluck('tool_slug');

        $tools = [];

        foreach ($slugs as $slug) {
            if (! is_string($slug) || ! $this->catalog->has($slug)) {
                continue;
            }

            $tool = $this->catalog->resolve($slug);

            if ($tool->usable()) {
                $tools[] = $tool;
            }

            if (count($tools) === self::LIMIT) {
                break;
            }
        }

        return $tools;
    }
}
