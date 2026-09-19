<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Models\User;
use App\Modules\Core\Domain\TaxonomyTerm;
use App\Modules\Core\Services\TaxonomyRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * دسته‌بندی مشترک میان همه محتواها.
 *
 * ارزش این جدول در اشتراکی‌بودنش است: وقتی کاربر روی «صدا» کلیک می‌کند باید
 * مقاله و ابزار و دوره صدا را با هم ببیند، نه فقط مقاله‌ها.
 */
final class TaxonomyTest extends TestCase
{
    use RefreshDatabase;

    private TaxonomyRegistry $taxonomy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxonomy = $this->app->make(TaxonomyRegistry::class);
    }

    public function test_the_known_taxonomies_come_from_configuration(): void
    {
        $this->assertArrayHasKey('health_domain', $this->taxonomy->known());
        $this->assertSame('حوزه بهداشت حرفه‌ای', $this->taxonomy->label('health_domain'));
    }

    public function test_an_unregistered_taxonomy_is_refused(): void
    {
        // اگر هر ماژول بتواند دسته‌بندی تازه بسازد، فیلتر مشترک سایت می‌شکند.
        $this->expectException(InvalidArgumentException::class);

        $this->taxonomy->terms('random_stuff');
    }

    public function test_terms_come_back_in_the_order_the_administrator_set(): void
    {
        TaxonomyTerm::query()->create(['taxonomy' => 'health_domain', 'slug' => 'chemical', 'name' => 'مواد شیمیایی', 'position' => 2]);
        TaxonomyTerm::query()->create(['taxonomy' => 'health_domain', 'slug' => 'noise', 'name' => 'صدا', 'position' => 1]);

        $this->assertSame(['صدا', 'مواد شیمیایی'], $this->taxonomy->terms('health_domain')->pluck('name')->all());
    }

    public function test_terms_of_one_taxonomy_never_leak_into_another(): void
    {
        TaxonomyTerm::query()->create(['taxonomy' => 'health_domain', 'slug' => 'noise', 'name' => 'صدا']);
        TaxonomyTerm::query()->create(['taxonomy' => 'industry', 'slug' => 'steel', 'name' => 'فولاد']);

        $this->assertCount(1, $this->taxonomy->terms('health_domain'));
        $this->assertCount(1, $this->taxonomy->terms('industry'));
    }

    public function test_the_tree_returns_roots_with_their_children(): void
    {
        $root = TaxonomyTerm::query()->create(['taxonomy' => 'health_domain', 'slug' => 'chemical', 'name' => 'مواد شیمیایی']);
        TaxonomyTerm::query()->create([
            'taxonomy' => 'health_domain',
            'slug' => 'solvents',
            'name' => 'حلال‌ها',
            'parent_id' => $root->getKey(),
        ]);

        $tree = $this->taxonomy->tree('health_domain');

        $this->assertCount(1, $tree, 'فقط ریشه‌ها در سطح اول می‌آیند.');
        $this->assertSame('حلال‌ها', $tree->first()?->children->first()?->name);
    }

    public function test_the_same_slug_can_exist_in_two_different_taxonomies(): void
    {
        TaxonomyTerm::query()->create(['taxonomy' => 'health_domain', 'slug' => 'general', 'name' => 'عمومی']);
        TaxonomyTerm::query()->create(['taxonomy' => 'industry', 'slug' => 'general', 'name' => 'عمومی']);

        $this->assertSame(2, TaxonomyTerm::query()->count());
    }

    public function test_one_content_item_can_carry_terms_from_several_taxonomies(): void
    {
        $noise = TaxonomyTerm::query()->create(['taxonomy' => 'health_domain', 'slug' => 'noise', 'name' => 'صدا']);
        $steel = TaxonomyTerm::query()->create(['taxonomy' => 'industry', 'slug' => 'steel', 'name' => 'فولاد']);

        $user = User::factory()->create();

        foreach ([$noise, $steel] as $term) {
            DB::table('taxonomables')->insert([
                'term_id' => $term->getKey(),
                'taxonomable_type' => User::class,
                'taxonomable_id' => $user->getKey(),
            ]);
        }

        $this->assertCount(2, $this->taxonomy->termsOf($user));
        $this->assertSame(['صدا'], $this->taxonomy->termsOf($user, 'health_domain')->pluck('name')->all());
    }
}
