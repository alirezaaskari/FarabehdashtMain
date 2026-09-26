<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Contracts\Taxonomy;
use App\Modules\Core\Domain\TaxonomyTerm;
use App\Support\Taxonomy\TermData;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;

final readonly class TaxonomyStore implements Taxonomy
{
    public function __construct(
        private TaxonomyRegistry $registry,
        private DatabaseManager $db,
    ) {}

    public function terms(string $taxonomy): array
    {
        return $this->map($this->registry->terms($taxonomy));
    }

    public function termsOf(string $type, int $id, string $taxonomy): array
    {
        $this->registry->terms($taxonomy);

        return $this->map(TaxonomyTerm::query()
            ->ofTaxonomy($taxonomy)
            ->whereIn('id', fn ($sub) => $sub->select('term_id')->from('taxonomables')
                ->where('taxonomable_type', $type)
                ->where('taxonomable_id', $id))
            ->get());
    }

    public function sync(string $type, int $id, string $taxonomy, array $termIds): void
    {
        $valid = TaxonomyTerm::query()->ofTaxonomy($taxonomy)->whereIn('id', $termIds)->pluck('id')->all();
        $all = TaxonomyTerm::query()->ofTaxonomy($taxonomy)->pluck('id')->all();

        $this->db->transaction(function () use ($type, $id, $valid, $all): void {
            $this->db->table('taxonomables')
                ->where('taxonomable_type', $type)
                ->where('taxonomable_id', $id)
                ->whereIn('term_id', $all)
                ->delete();

            $this->db->table('taxonomables')->insert(array_map(static fn (int $termId): array => [
                'term_id' => $termId,
                'taxonomable_type' => $type,
                'taxonomable_id' => $id,
            ], $valid));
        });
    }

    public function taggedIds(string $type, string $taxonomy, string $slug): array
    {
        return $this->db->table('taxonomables')
            ->join('taxonomy_terms', 'taxonomy_terms.id', '=', 'taxonomables.term_id')
            ->where('taxonomy_terms.taxonomy', $taxonomy)
            ->where('taxonomy_terms.slug', $slug)
            ->where('taxonomables.taxonomable_type', $type)
            ->pluck('taxonomables.taxonomable_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TaxonomyTerm>  $terms
     * @return list<TermData>
     */
    private function map(Collection $terms): array
    {
        return $terms
            ->sortBy([['position', 'asc'], ['name', 'asc']])
            ->map(static fn (TaxonomyTerm $term): TermData => new TermData($term->id, $term->slug, $term->name, $term->description))
            ->values()
            ->all();
    }
}
