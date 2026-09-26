<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Modules\Tools\Home\ToolHighlights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * محاسبه سریع و بخش ابزارهای صفحه اصلی.
 *
 * نتیجه زنده از همان موتور محاسبه می‌آید (`tools.preview`)، نه از فرمولی در
 * مرورگر؛ و بخش ابزارها گروه‌های عامل زیان‌آور را با شمار واقعی نشان می‌دهد.
 */
final class QuickConvertTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_preview_returns_the_engine_result_as_json(): void
    {
        $this->postJson(route('tools.preview', 'ppm-to-mass-concentration'), [
            'concentration' => '1',
            'molecular_weight' => '78.11',
            'temperature' => '25',
            'pressure' => '101.325',
        ])
            ->assertOk()
            ->assertJsonPath('rows.0.unit', 'mg/m³')
            ->assertJsonPath('rows.0.value', '3.1947');
    }

    public function test_an_invalid_preview_names_the_field_instead_of_guessing(): void
    {
        $this->postJson(route('tools.preview', 'ppm-to-mass-concentration'), [
            'concentration' => 'سه',
            'molecular_weight' => '78.11',
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['concentration']]);
    }

    public function test_the_homepage_offers_both_directions_and_common_substances(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('محاسبه سریع غلظت')
            ->assertSee(route('tools.calculate', 'ppm-to-mass-concentration').'#result', false)
            ->assertSee('data-molecular-weight="78.11"', false)
            ->assertSee('mg/m³ → ppm');
    }

    public function test_tools_are_grouped_by_hazard_with_real_counts(): void
    {
        $section = $this->app->make(ToolHighlights::class)->homeSection();

        $this->assertNotNull($section);
        $this->assertSame('عوامل شیمیایی', $section->items[0]->title);
        $this->assertStringEndsWith('#group-chemical', $section->items[0]->url);
        $this->assertSame(route('tools.advisor'), $section->feature?->url);
    }
}
