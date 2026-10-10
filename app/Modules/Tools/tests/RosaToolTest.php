<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Models\User;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Services\ToolCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ارزیابی ایستگاه کار اداری ROSA: فرم گام‌به‌گام، نتیجه، گزارش و دستیار انتخاب روش.
 */
final class RosaToolTest extends TestCase
{
    use RefreshDatabase;

    /** مثال حل‌شده NTP 1173 (INSST): صندلی ۶، امتیاز نهایی ۶. */
    private const array INSST_EXAMPLE = [
        'chair_height' => '3', 'desk_no_leg_room' => '0', 'chair_height_fixed' => '1', 'seat_depth' => '1', 'seat_depth_fixed' => '0',
        'armrests' => '2', 'armrest_hard' => '0', 'armrest_wide' => '0', 'armrest_fixed' => '1',
        'backrest' => '2', 'desk_too_high' => '0', 'backrest_fixed' => '1', 'chair_duration' => '3',
        'monitor' => '1', 'monitor_far' => '0', 'monitor_neck_twist' => '0', 'monitor_glare' => '0', 'monitor_no_holder' => '1', 'monitor_duration' => '3',
        'phone' => '1', 'phone_neck_hold' => '0', 'phone_no_handsfree' => '0', 'phone_duration' => '2',
        'mouse' => '2', 'mouse_separate_surface' => '0', 'mouse_pinch' => '0', 'mouse_palmrest' => '1', 'mouse_duration' => '3',
        'keyboard' => '1', 'keyboard_deviation' => '0', 'keyboard_too_high' => '0', 'keyboard_overhead' => '0',
        'keyboard_platform_fixed' => '0', 'keyboard_duration' => '2',
    ];

    public function test_every_input_sits_in_exactly_one_step_and_is_a_choice_or_a_toggle(): void
    {
        $tool = $this->app->make(ToolCatalog::class)->resolve('rosa');
        $definition = $tool->definition;

        $placed = array_merge(...array_column($definition->steps, 'inputs'));
        $inputs = array_keys($tool->formula->inputs());

        $this->assertEqualsCanonicalizing($inputs, $placed);
        $this->assertSame(count($placed), count(array_unique($placed)));

        foreach ($inputs as $key) {
            $this->assertTrue($definition->isToggle($key) || $definition->choicesFor($key) !== [], $key);
        }
    }

    public function test_the_page_is_a_step_form_that_points_to_the_method_advisor(): void
    {
        $this->get(route('tools.show', 'rosa'))
            ->assertOk()
            ->assertSee('data-tool-steps', false)
            ->assertSee('گام ۱ از ۸')
            ->assertSee('<input type="hidden" name="chair_height_fixed" value="0">', false)
            ->assertSee('بیش از ۴ ساعت در روز، یا بیش از ۱ ساعت پیوسته')
            ->assertSee(route('tools.advisor', ['hazard' => 'ergonomics']), false)
            ->assertDontSee('ارزیابی سمت دیگر بدن');
    }

    public function test_the_insst_example_scores_six_and_names_the_chair(): void
    {
        $this->post(route('tools.calculate', 'rosa'), self::INSST_EXAMPLE)
            ->assertOk()
            ->assertSee('امتیاز نهایی ROSA')
            ->assertSee('امتیاز ۵ یا بیشتر، سطح اقدام ROSA')
            ->assertSee('امتیاز نهایی را صندلی می‌سازد');
    }

    public function test_a_saved_assessment_reports_choices_in_words_not_codes(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('tools.calculations.store', 'rosa'), [...self::INSST_EXAMPLE, 'label' => 'میز حسابداری'])
            ->assertRedirect();

        $saved = SavedCalculation::query()->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta(6.0, $saved->outputs['rosa_score']['value'], 1e-9);

        $this->get(route('tools.calculations.print', $saved->uuid))
            ->assertOk()
            ->assertSee('خیلی بلند: زاویه زانو بیش از ۹۰ درجه')
            ->assertSee('بیش از ۴ ساعت در روز، یا بیش از ۱ ساعت پیوسته');
    }

    public function test_the_advisor_offers_all_three_posture_methods_for_ergonomics(): void
    {
        $this->get(route('tools.advisor', ['hazard' => 'ergonomics', 'stage' => 'measure']))
            ->assertOk()
            ->assertSee('کارمند بیشتر روز پشت میز با رایانه کار می‌کند.')
            ->assertSee('پوسچر کارگر مدام عوض می‌شود')
            ->assertSee('کارگر بیشتر در یک جا نشسته یا ایستاده است');
    }
}
