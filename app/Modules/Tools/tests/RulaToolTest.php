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
 * ارزیابی پوسچر RULA: فرم گام‌به‌گام، نتیجه و گزارش ذخیره‌شده.
 */
final class RulaToolTest extends TestCase
{
    use RefreshDatabase;

    /** مثال ۱ راهنمای Ergo-Plus (چکش‌کاری کویل): امتیاز نهایی ۶. */
    private const array HAMMERING = [
        'upper_arm' => '3', 'shoulder_raised' => '1', 'arm_abducted' => '1', 'arm_supported' => '0',
        'lower_arm' => '1', 'lower_arm_out' => '0', 'wrist' => '2', 'wrist_bent' => '0', 'wrist_twist' => '1',
        'arm_muscle' => '1', 'arm_force' => '3', 'neck' => '1', 'neck_twisted' => '0', 'neck_side_bent' => '0',
        'trunk' => '2', 'trunk_twisted' => '0', 'trunk_side_bent' => '1', 'legs' => '1',
        'body_muscle' => '0', 'body_force' => '0',
    ];

    public function test_every_input_sits_in_exactly_one_step_and_is_a_choice_or_a_toggle(): void
    {
        $tool = $this->app->make(ToolCatalog::class)->resolve('rula');
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
        $this->get(route('tools.show', 'rula'))
            ->assertOk()
            ->assertSee('data-tool-steps', false)
            ->assertSee('گام ۱ از ۷')
            ->assertSee('class="tool-choice"', false)
            ->assertSee('class="tool-figure"', false)
            ->assertSee('<input type="hidden" name="shoulder_raised" value="0">', false)
            ->assertSee('۴۵ تا ۹۰ درجه جلو');
    }

    public function test_the_hammering_example_scores_six_and_names_the_upper_arm(): void
    {
        $this->post(route('tools.calculate', 'rula'), self::HAMMERING)
            ->assertOk()
            ->assertSee('امتیاز نهایی RULA')
            ->assertSee('سطح اقدام ۳: بررسی و تغییر باید به‌زودی انجام شود.')
            ->assertSee('بیشترین سهم در امتیاز پوسچر را بازو (۵ از ۶) دارد')
            ->assertSee('ارزیابی سمت دیگر بدن');
    }

    public function test_the_other_side_link_reopens_the_form_with_the_same_answers(): void
    {
        $this->get(route('tools.show', ['rula', ...self::HAMMERING]))
            ->assertOk()
            ->assertSee('name="upper_arm" value="3" checked', false);
    }

    public function test_a_saved_assessment_reports_choices_in_words_not_codes(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('tools.calculations.store', 'rula'), [...self::HAMMERING, 'label' => 'ایستگاه چکش'])
            ->assertRedirect();

        $saved = SavedCalculation::query()->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta(6.0, $saved->outputs['rula_score']['value'], 1e-9);

        $this->get(route('tools.calculations.print', $saved->uuid))
            ->assertOk()
            ->assertSee('۴۵ تا ۹۰ درجه جلو')
            ->assertSee('بیش از ۱۰ کیلوگرم ایستا یا تکراری؛ یا ضربه و نیروی ناگهانی');
    }
}
