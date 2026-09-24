<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Search;

use App\Contracts\SearchSource;
use App\Modules\Chemicals\Domain\CasNumber;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchHit;
use App\Support\Search\SearchQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

/**
 * بانک مواد شیمیایی در جست‌وجوی داخلی.
 *
 * شماره CAS معتبر (با رقم کنترلی درست) که ماده منتشرشده‌ای دارد، مستقیم به
 * صفحه همان ماده می‌رود (DEC-25).
 */
final readonly class SubstanceSearch implements SearchSource
{
    public function search(SearchQuery $query, int $limit): ?SearchGroup
    {
        if (! Route::has('chemicals.show')) {
            return null;
        }

        $matches = Substance::query()->published()->where(function (Builder $any) use ($query): void {
            $query->constrain($any, ['name_fa', 'name_en', 'cas_number'])
                ->orWhereHas('synonyms', static fn (Builder $synonym) => $query->constrain($synonym, ['name']));
        });

        return new SearchGroup(
            key: 'chemicals',
            title: 'مواد شیمیایی',
            hits: (clone $matches)->orderBy('name_fa')->limit($limit)->get()
                ->map(static fn (Substance $substance): SearchHit => new SearchHit(
                    title: $substance->name_fa,
                    url: route('chemicals.show', $substance->slug),
                    summary: $substance->name_en,
                    code: $substance->cas_number,
                ))
                ->values()
                ->all(),
            total: $matches->count(),
            order: 20,
            moreUrl: Route::has('chemicals.index') ? route('chemicals.index', ['q' => $query->raw]) : null,
        );
    }

    public function directUrl(SearchQuery $query): ?string
    {
        if (! Route::has('chemicals.show') || ! CasNumber::isValid($query->raw)) {
            return null;
        }

        $slug = Substance::query()
            ->published()
            ->where('cas_number', (string) CasNumber::fromString($query->raw))
            ->value('slug');

        return is_string($slug) ? route('chemicals.show', $slug) : null;
    }
}
