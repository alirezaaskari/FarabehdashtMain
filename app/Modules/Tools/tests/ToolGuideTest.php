<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Modules\Tools\Services\ToolCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * راهنمای هر ابزار: به چه کار می‌آید و هر ورودی را از کجا بیاوریم.
 */
final class ToolGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_tool_explains_its_purpose_and_the_source_of_every_input(): void
    {
        $catalog = $this->app->make(ToolCatalog::class);

        foreach ($catalog->definitions() as $slug => $definition) {
            $this->assertNotSame('', trim((string) $definition->purpose), $slug);
            $this->assertNotEmpty($definition->uses, $slug);

            // ورودی تازه‌ای که به فرمول اضافه شود، بی‌راهنما نمی‌ماند.
            foreach (array_keys($catalog->resolve($slug)->formula->inputs()) as $key) {
                $this->assertNotSame('', trim((string) $definition->sourceFor($key)), $slug.'.'.$key);
            }
        }
    }

    public function test_no_guide_is_left_for_a_tool_or_input_that_does_not_exist(): void
    {
        $catalog = $this->app->make(ToolCatalog::class);

        foreach ((array) config('tools.guides') as $slug => $guide) {
            $this->assertTrue($catalog->has($slug), $slug);

            $inputs = $catalog->resolve($slug)->formula->inputs();

            foreach (array_keys($guide['sources'] ?? []) as $key) {
                $this->assertArrayHasKey($key, $inputs, $slug.'.'.$key);
            }
        }
    }

    public function test_the_tool_page_shows_the_purpose_and_where_each_value_comes_from(): void
    {
        $this->get(route('tools.show', 'ppm-to-mass-concentration'))
            ->assertOk()
            ->assertSee('این ابزار به چه کار می‌آید؟')
            ->assertSee('data-input-source="molecular_weight"', false)
            ->assertSee('data-input-source="pressure"', false)
            ->assertSee('برگه اطلاعات ایمنی');
    }

    public function test_field_mode_keeps_the_form_short(): void
    {
        $this->get(route('tools.show', ['niosh-lifting', 'field' => 1]))
            ->assertOk()
            ->assertDontSee('data-input-source', false)
            ->assertDontSee('این ابزار به چه کار می‌آید؟');
    }
}
