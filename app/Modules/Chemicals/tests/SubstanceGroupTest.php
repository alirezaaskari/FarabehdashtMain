<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Tests;

use App\Contracts\Taxonomy;
use App\Modules\Chemicals\Actions\PublishSubstance;
use App\Modules\Chemicals\Actions\SaveSubstance;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Core\Domain\TaxonomyTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** گروه ماده و فیلتر بانک مواد (بخش ۱۸-۱۱). */
final class SubstanceGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_filters_by_group_and_the_page_links_back(): void
    {
        $solvents = TaxonomyTerm::query()->create(['taxonomy' => 'chemical_group', 'slug' => 'solvents', 'name' => 'حلال‌ها']);
        TaxonomyTerm::query()->create(['taxonomy' => 'chemical_group', 'slug' => 'metals', 'name' => 'فلزات']);

        $this->publish('تولوئن', 'Toluene', '108-88-3', 'toluene', [$solvents->id]);
        $this->publish('سرب', 'Lead', '7439-92-1', 'lead', []);

        $this->get(route('chemicals.index'))->assertOk()->assertSee('حلال‌ها')->assertSee('فلزات')->assertSee('سرب');

        $this->get(route('chemicals.index', ['group' => 'solvents']))
            ->assertOk()
            ->assertSee('تولوئن')
            ->assertDontSee('>سرب<', false)
            ->assertSee('aria-current="true"', false);

        $this->get(route('chemicals.index', ['group' => 'unknown']))->assertOk()->assertSee('سرب');

        $this->get(route('chemicals.show', 'toluene'))
            ->assertOk()
            ->assertSee(route('chemicals.index', ['group' => 'solvents']), false);
    }

    public function test_a_save_without_groups_keeps_them(): void
    {
        $solvents = TaxonomyTerm::query()->create(['taxonomy' => 'chemical_group', 'slug' => 'solvents', 'name' => 'حلال‌ها']);
        $substance = $this->publish('تولوئن', 'Toluene', '108-88-3', 'toluene', [$solvents->id]);

        $data = $this->data('تولوئن', 'Toluene', '108-88-3', 'toluene');
        $this->app->make(SaveSubstance::class)->handle($substance, $data, null);

        $this->assertCount(1, $this->app->make(Taxonomy::class)->termsOf(Substance::class, $substance->id, 'chemical_group'));
    }

    public function test_no_group_row_without_groups(): void
    {
        $this->get(route('chemicals.index'))->assertOk()->assertDontSee('aria-label="گروه ماده"', false);
    }

    /** @param  list<int>  $groups */
    private function publish(string $fa, string $en, string $cas, string $slug, array $groups): Substance
    {
        $substance = $this->app->make(SaveSubstance::class)->handle(null, [...$this->data($fa, $en, $cas, $slug), 'groups' => $groups], null);

        return $this->app->make(PublishSubstance::class)->handle($substance, null);
    }

    /** @return array<string, mixed> */
    private function data(string $fa, string $en, string $cas, string $slug): array
    {
        return [
            'name_fa' => $fa, 'name_en' => $en, 'cas_number' => $cas, 'slug' => $slug,
            'synonyms' => [], 'formula' => null, 'molar_mass' => null, 'physical_state' => null, 'description' => null,
            'limits' => [[
                'authority' => 'acgih', 'type' => 'twa', 'value' => 20, 'unit' => 'ppm', 'note' => null,
                'reference_title' => 'Reference', 'reference_edition' => null, 'reference_year' => 2024,
            ]],
            'routes' => ['استنشاق'], 'symptoms' => ['سردرد'], 'protection' => [],
            'sampling_media' => null, 'sampling_flow' => null, 'analysis_method' => null, 'method_number' => null,
        ];
    }
}
