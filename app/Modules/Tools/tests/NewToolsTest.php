<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Modules\Tools\Domain\Enums\Hazard;
use App\Modules\Tools\Services\ToolCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ابزارهای بخش ۱۸-۴ (DEC-43): از فرم تا نتیجه، در سایت واقعی.
 */
final class NewToolsTest extends TestCase
{
    use RefreshDatabase;

    private const array TOOLS = [
        'daily-noise-exposure',
        'noise-distance-attenuation',
        'mixture-exposure-index-ppm',
        'mixture-exposure-index-mass',
        'brief-scala-adjustment',
        'dilution-ventilation',
        'hand-arm-vibration',
        'whole-body-vibration',
        'niosh-lifting',
    ];

    public function test_every_new_tool_page_opens_with_its_field_example(): void
    {
        foreach (self::TOOLS as $slug) {
            $this->get(route('tools.show', $slug))
                ->assertOk()
                ->assertSee('نمونه:');
        }
    }

    public function test_the_catalog_reaches_twenty_tools(): void
    {
        // طرح بخش ۱۸ (DEC-43): دست‌کم بیست ابزار؛ ۱۳ ابزار بود و ۹ ابزار آمد.
        $this->assertGreaterThanOrEqual(20, count($this->app->make(ToolCatalog::class)->usable()));
    }

    public function test_vibration_and_ergonomics_now_have_tools_in_the_advisor(): void
    {
        $this->assertSame('vibration', Hazard::Vibration->category()?->value);
        $this->assertSame('ergonomics', Hazard::Ergonomics->category()?->value);

        $this->get(route('tools.advisor', ['hazard' => 'vibration', 'stage' => 'measure']))
            ->assertOk()
            ->assertSee('سؤال ۳ از ۳')
            ->assertSee('سنگ‌فرز');
    }

    public function test_niosh_lifting_uses_choices_and_computes_the_recommended_weight(): void
    {
        $this->get(route('tools.show', 'niosh-lifting'))
            ->assertOk()
            ->assertSee('<select id="coupling"', escape: false)
            ->assertSee('متوسط — ۱ تا ۲ ساعت');

        $this->post(route('tools.calculate', 'niosh-lifting'), [
            'load' => '15',
            'horizontal' => '40',
            'vertical' => '30',
            'travel' => '100',
            'asymmetry' => '30',
            'frequency' => '1',
            'duration' => '2',
            'coupling' => '2',
        ])->assertOk()
            ->assertSee('حد وزن توصیه‌شده (RWL)')
            ->assertSee('8.1286');
    }

    public function test_a_frequency_outside_the_niosh_table_is_refused_with_a_reason(): void
    {
        $this->post(route('tools.calculate', 'niosh-lifting'), [
            'load' => '10',
            'horizontal' => '30',
            'vertical' => '50',
            'travel' => '50',
            'asymmetry' => '0',
            'frequency' => '12',
            'duration' => '8',
            'coupling' => '1',
        ])->assertOk()
            ->assertSee('بیرون از دامنه معادله');
    }

    public function test_hand_arm_vibration_is_computed_from_two_tools(): void
    {
        $this->post(route('tools.calculate', 'hand-arm-vibration'), [
            'magnitudes' => ['4', '3'],
            'durations' => ['2', '4'],
        ])->assertOk()->assertSee('2.9155');
    }

    public function test_brief_scala_refuses_a_week_shorter_than_one_shift(): void
    {
        $this->post(route('tools.calculate', 'brief-scala-adjustment'), [
            'shift_hours' => '12',
            'weekly_hours' => '10',
        ])->assertOk()->assertSee('ساعت کار هفتگی نمی‌تواند کمتر از مدت یک شیفت باشد');
    }
}
