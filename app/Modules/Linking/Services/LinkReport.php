<?php

declare(strict_types=1);

namespace App\Modules\Linking\Services;

use App\Modules\Linking\Domain\InternalLink;
use App\Modules\Linking\Domain\LinkBlock;
use App\Modules\Linking\Domain\LinkTargetRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * گزارش پیوندهای داخلی برای مدیر محتوا.
 *
 * «یتیم» صفحه‌ای است که هیچ متنی به آن پیوند نمی‌دهد: یا کسی درباره‌اش
 * ننوشته، یا عنوانش آن‌طور که مردم می‌نویسند نیست. هر دو کار سردبیر است.
 */
final readonly class LinkReport
{
    /** @return array{targets: int, links: int, sources: int, orphans: int} */
    public function totals(): array
    {
        return [
            'targets' => LinkTargetRecord::query()->count(),
            'links' => InternalLink::query()->count(),
            'sources' => InternalLink::query()->distinct()->count('source_key'),
            'orphans' => $this->orphansQuery()->count(),
        ];
    }

    /** @return Collection<int, LinkTargetRecord> */
    public function orphans(int $limit = 50): Collection
    {
        return $this->orphansQuery()->orderBy('key')->limit($limit)->get();
    }

    /** @return Collection<int, object{target_key: string, target_title: string, target_url: string, mentions: int}> */
    public function mostLinked(int $limit = 10): Collection
    {
        /** @var Collection<int, object{target_key: string, target_title: string, target_url: string, mentions: int}> */
        return InternalLink::query()
            ->toBase()
            ->selectRaw('target_key, target_title, target_url, count(*) as mentions')
            ->groupBy('target_key', 'target_title', 'target_url')
            ->orderByDesc('mentions')
            ->orderBy('target_key')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, LinkBlock> */
    public function blocks(): Collection
    {
        return LinkBlock::query()->orderByDesc('id')->get();
    }

    /** @return Builder<LinkTargetRecord> */
    private function orphansQuery(): Builder
    {
        return LinkTargetRecord::query()->whereNotIn('key', InternalLink::query()->select('target_key'));
    }
}
