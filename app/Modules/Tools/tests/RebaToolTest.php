<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Models\User;
use App\Modules\Tools\Domain\AngleFigure;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Services\ToolCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ارزیابی پوسچر REBA: فرم گام‌به‌گام، نتیجه و گزارش ذخیره‌شده.
 */
final class RebaToolTest extends TestCase
{
    use RefreshDatabase;

    /** مثال راهنمای Ergo-Plus (بلند کردن بار با دست بالای سر): امتیاز نهایی ۹. */
    private const array OVERHEAD_LIFT = [
        'trunk' => '2', 'trunk_twisted_or_bent' => '1', 'neck' => '1', 'neck_twisted_or_bent' => '0',
        'legs' => '1', 'knees' => '0', 'load_class' => '1', 'load_shock' => '0',
        'upper_arm' => '4', 'shoulder_raised' => '1', 'arm_abducted_or_rotated' => '1', 'arm_supported' => '0',
        'lower_arm' => '2', 'wrist' => '2', 'wrist_twisted_or_bent' => '1', 'grip' => '1',
        'static_posture' => '0', 'repeated_action' => '1', 'rapid_change' => '0',
    ];

    public function test_every_input_sits_in_exactly_one_step_and_is_a_choice_or_a_toggle(): void
    {
        $tool = $this->app->make(ToolCatalog::class)->resolve('reba');
        $definition = $tool->definition;

        $placed = array_merge(...array_column($definition->steps, 'inputs'));
        $inputs = array_keys($tool->formula->inputs());

        $this->assertEqualsCanonicalizing($inputs, $placed);
        $this->assertSame(count($placed), count(array_unique($placed)));

        foreach ($inputs as $key) {
            $this->assertTrue($definition->isToggle($key) || $definition->choicesFor($key) !== [], $key);
        }

        foreach ($definition->figures as $key => $figure) {
            $this->assertContains($figure['segment'], AngleFigure::segments(), $key);
            $this->assertSame(array_keys($definition->choicesFor($key)), array_keys($figure['ranges']), $key);
        }
    }

    public function test_the_page_is_a_step_form_with_angle_cards_and_toggles(): void
    {
        $this->get(route('tools.show', 'reba'))
            ->assertOk()
            ->assertSee('data-tool-steps', false)
            ->assertSee('گام ۱ از ۸')
            ->assertSee('class="tool-figure"', false)
            ->assertSee('<input type="hidden" name="load_shock" value="0">', false)
            ->assertSee('۲۰ تا ۶۰ درجه جلو، یا بیش از ۲۰ درجه عقب');
    }

    public function test_the_overhead_lift_example_scores_nine_and_names_the_leaders(): void
    {
        $this->post(route('tools.calculate', 'reba'), self::OVERHEAD_LIFT)
            ->assertOk()
            ->assertSee('امتیاز نهایی REBA')
            ->assertSee('سطح اقدام ۳، ریسک زیاد: اقدام باید به‌زودی انجام شود.')
            ->assertSee('بیشترین سهم در امتیاز پوسچر را بازو (۶ از ۶)، ساعد (۲ از ۲) و مچ (۳ از ۳) دارد')
            ->assertSee('ارزیابی سمت دیگر بدن');
    }

    public function test_a_saved_assessment_reports_choices_in_words_not_codes(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('tools.calculations.store', 'reba'), [...self::OVERHEAD_LIFT, 'label' => 'قفسه بالا'])
            ->assertRedirect();

        $saved = SavedCalculation::query()->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta(9.0, $saved->outputs['reba_score']['value'], 1e-9);

        $this->get(route('tools.calculations.print', $saved->uuid))
            ->assertOk()
            ->assertSee('بیش از ۹۰ درجه جلو')
            ->assertSee('متوسط: دستگیره پذیرفتنی ولی نه ایده‌آل، یا تکیه دادن بار به عضو دیگر بدن');
    }
}
