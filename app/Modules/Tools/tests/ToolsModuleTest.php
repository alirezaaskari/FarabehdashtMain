<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\Modules\ModuleRegistry;
use Farabehdasht\CalcEngine\FormulaRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * سلامت خود ماژول و پیوندش با موتور محاسبات.
 */
final class ToolsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_is_enabled(): void
    {
        $this->assertTrue($this->app->make(ModuleRegistry::class)->isEnabled('Tools'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function toolSlugs(): iterable
    {
        /** @var array<string, array<string, mixed>> $catalog */
        $catalog = require dirname(__DIR__).'/config/tools.php';

        foreach (array_keys($catalog['catalog']) as $slug) {
            yield $slug => [$slug];
        }
    }

    #[DataProvider('toolSlugs')]
    public function test_every_tool_points_at_a_formula_that_exists(string $slug): void
    {
        // ابزاری که به رابطه‌ای ناموجود اشاره کند، فقط وقتی کسی صفحه‌اش را باز
        // کند خطا می‌دهد. این تست همان لحظه را به زمان CI می‌آورد.
        $catalog = $this->app->make(ToolCatalog::class);
        $registry = $this->app->make(FormulaRegistry::class);

        $this->assertTrue($registry->has($catalog->definitions()[$slug]->formulaId));
    }

    #[DataProvider('toolSlugs')]
    public function test_every_tool_page_renders(string $slug): void
    {
        $this->get(route('tools.show', $slug))
            ->assertOk()
            ->assertSee('محاسبه کن');
    }

    #[DataProvider('toolSlugs')]
    public function test_every_tool_hint_names_a_real_input(string $slug): void
    {
        // راهنمایی که کلیدش با هیچ ورودی نخواند، بی‌صدا نمایش داده نمی‌شود.
        $tool = $this->app->make(ToolCatalog::class)->resolve($slug);
        $inputs = array_keys($tool->formula->inputs());

        foreach (array_keys($tool->definition->hints) as $key) {
            $this->assertContains($key, $inputs, sprintf('راهنمای «%s» در ابزار %s ورودی ندارد.', $key, $slug));
        }

        foreach (array_keys($tool->definition->defaults) as $key) {
            $this->assertContains($key, $inputs, sprintf('پیش‌فرض «%s» در ابزار %s ورودی ندارد.', $key, $slug));
        }
    }

    #[DataProvider('toolSlugs')]
    public function test_every_output_has_a_persian_label(string $slug): void
    {
        /** @var array<string, string> $labels */
        $labels = (array) config('tools.output_labels', []);

        $tool = $this->app->make(ToolCatalog::class)->resolve($slug);

        foreach (array_keys($tool->formula->outputs()) as $key) {
            $this->assertArrayHasKey($key, $labels, sprintf('خروجی «%s» برچسب فارسی ندارد.', $key));
        }

        foreach (array_keys($tool->formula->inputs()) as $key) {
            $this->assertArrayHasKey($key, $labels, sprintf('ورودی «%s» برچسب فارسی برای گزارش چاپی ندارد.', $key));
        }
    }

    public function test_the_hub_lists_every_usable_tool(): void
    {
        $catalog = $this->app->make(ToolCatalog::class);

        $response = $this->get(route('tools.index'))->assertOk();

        foreach ($catalog->usable() as $tool) {
            $response->assertSee($tool->definition->title);
        }
    }

    public function test_tools_are_grouped_by_category_in_enum_order(): void
    {
        $groups = array_keys($this->app->make(ToolCatalog::class)->grouped());

        $this->assertSame(['heat', 'noise', 'chemical', 'lighting', 'ventilation'], $groups);
    }

    public function test_an_unknown_slug_is_a_404(): void
    {
        $this->get('/tools/nothing-like-this')->assertNotFound();
    }

    public function test_the_catalog_resolves_the_latest_version_by_default(): void
    {
        $tool = $this->app->make(ToolCatalog::class)->resolve('wbgt-indoor');

        $this->assertInstanceOf(ResolvedTool::class, $tool);
        $this->assertSame('1.0.0', $tool->version());
        $this->assertFalse($tool->versionPinned);
    }
}
