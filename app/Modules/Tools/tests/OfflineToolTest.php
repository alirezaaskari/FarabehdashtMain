<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Models\User;
use App\Modules\Tools\Domain\SavedCalculation;
use Farabehdasht\CalcEngine\Formulas\DefaultFormulas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ابزار بدون اینترنت و نصب روی گوشی (بخش ۱۸-۱۰).
 *
 * هم‌ارزی خود فرمول‌ها را `npm run test:formulas` می‌پاید؛ این‌جا فقط سیم‌کشی
 * سمت سرور: مشخصات آفلاین صفحه، پاسخ JSON صف ذخیره و پاک شدن حافظه در خروج.
 */
final class OfflineToolTest extends TestCase
{
    use RefreshDatabase;

    private const INPUTS = ['natural_wet_bulb' => '25', 'globe' => '35'];

    public function test_the_tool_page_carries_the_offline_spec_of_the_formula_it_runs(): void
    {
        $html = $this->get(route('tools.show', 'wbgt-indoor'))->assertOk()->getContent();

        $this->assertIsString($html);
        $this->assertStringContainsString('data-offline-tool', $html);
        $this->assertStringContainsString('data-tool-form', $html);
        $this->assertMatchesRegularExpression('#<script type="application/json" data-offline-spec>(.+?)</script>#s', $html);

        preg_match('#<script type="application/json" data-offline-spec>(.+?)</script>#s', $html, $match);
        $spec = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('wbgt-indoor@1.0.0', $spec['formula']);
        $this->assertSame(['natural_wet_bulb', 'globe'], array_keys($spec['inputs']));
        $this->assertArrayHasKey('wbgt', $spec['outputs']);
        $this->assertSame('°C', $spec['outputs']['wbgt']['unit']);
    }

    public function test_every_formula_has_an_offline_port(): void
    {
        $ported = '';

        foreach (glob(resource_path('js/offline/formulas/*.js')) ?: [] as $file) {
            $ported .= (string) file_get_contents($file);
        }

        foreach (DefaultFormulas::all() as $formula) {
            $this->assertStringContainsString("'".$formula->id().'@'.$formula->version()."'", $ported);
        }
    }

    public function test_the_offline_queue_saves_through_json(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('tools.calculations.store', 'wbgt-indoor'), [...self::INPUTS, 'label' => 'سالن ۲'])
            ->assertCreated()
            ->assertJsonStructure(['url']);

        $this->assertSame('سالن ۲', SavedCalculation::query()->sole()->label);

        $this->actingAs($user)
            ->postJson(route('tools.calculations.store', 'wbgt-indoor'), ['natural_wet_bulb' => '25'])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['globe']]);
    }

    public function test_the_free_plan_limit_is_a_final_json_answer(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson(route('tools.calculations.store', 'wbgt-indoor'), self::INPUTS)->assertCreated();
        }

        $this->actingAs($user)
            ->postJson(route('tools.calculations.store', 'wbgt-indoor'), self::INPUTS)
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }

    public function test_signing_out_clears_the_offline_cache(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('identity.signout'))
            ->assertHeader('Clear-Site-Data', '"cache"');
    }

    public function test_the_offline_page_manifest_and_worker_are_served(): void
    {
        $this->get(route('tools.offline'))->assertOk()->assertSee('اینترنت وصل نیست');
        $this->get(route('tools.index'))->assertOk()->assertSee('rel="manifest"', false)->assertSee('نصب روی گوشی');

        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('standalone', $manifest['display']);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }

        $this->assertStringContainsString("'/tools/offline'", (string) file_get_contents(public_path('sw.js')));
    }
}
