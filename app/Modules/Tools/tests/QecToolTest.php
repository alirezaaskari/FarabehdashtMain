<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Models\User;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Services\ToolCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ارزیابی سریع مواجهه QEC: فرم گام‌به‌گام، نتیجه هر ناحیه، گزارش و دستیار انتخاب روش.
 */
final class QecToolTest extends TestCase
{
    use RefreshDatabase;

    /** نمونه «تغذیه قیف» OHCOW: کمر ۴۰، شانه ۴۰، مچ ۳۸، گردن ۱۴، شاخص ۷۵٪. */
    private const array FEEDER = [
        'back_posture' => '3', 'back_movement' => '4', 'hand_height' => '3', 'arm_movement' => '2',
        'wrist_posture' => '2', 'hand_repetition' => '1', 'neck_posture' => '2',
        'handled_weight' => '2', 'task_duration' => '3', 'hand_force' => '3', 'visual_demand' => '1',
        'driving_time' => '1', 'vibration_time' => '1', 'work_pace' => '2', 'work_stress' => '2',
    ];

    public function test_every_input_sits_in_exactly_one_step_as_a_choice(): void
    {
        $tool = $this->app->make(ToolCatalog::class)->resolve('qec');

        $placed = array_merge(...array_column($tool->definition->steps, 'inputs'));
        $inputs = array_keys($tool->formula->inputs());

        $this->assertEqualsCanonicalizing($inputs, $placed);
        $this->assertSame(count($placed), count(array_unique($placed)));

        foreach ($inputs as $key) {
            $this->assertNotSame([], $tool->definition->choicesFor($key), $key);
        }
    }

    public function test_the_page_is_a_step_form_that_asks_the_worker_too(): void
    {
        $this->get(route('tools.show', 'qec'))
            ->assertOk()
            ->assertSee('data-tool-steps', false)
            ->assertSee('گام ۱ از ۷')
            ->assertSee('از کارگر بپرسید: بار، مدت و نیرو')
            ->assertSee('جابه‌جایی بار: حرکت کمر زیاد، حدود ۸ بار در دقیقه')
            ->assertDontSee('ارزیابی سمت دیگر بدن');
    }

    public function test_the_feeder_example_rates_each_area_and_names_what_to_fix_first(): void
    {
        $this->post(route('tools.calculate', 'qec'), self::FEEDER)
            ->assertOk()
            ->assertSee('شاخص مواجهه کل QEC (E)')
            ->assertSee('شاخص مواجهه ۷۰ درصد یا بیشتر: بررسی و تغییر فوری.')
            ->assertSee('کمر زیاد (۴۰)')
            ->assertSee('پوسچر کمر و مدت کار (۱۰)')
            ->assertSee('سرعت کار (۴) و استرس کار (۴)');
    }

    public function test_shortening_the_task_lowers_the_saved_exposure_and_prints_answers_in_words(): void
    {
        $user = User::factory()->create();

        foreach (['پیش' => '3', 'پس' => '2'] as $label => $duration) {
            $this->actingAs($user)
                ->post(route('tools.calculations.store', 'qec'), [...self::FEEDER, 'task_duration' => $duration, 'label' => $label])
                ->assertRedirect();
        }

        [$before, $after] = SavedCalculation::query()->oldest('id')->get()->all();

        $this->assertEqualsWithDelta(75.0, $before->outputs['qec_exposure']['value'], 1e-9);
        $this->assertEqualsWithDelta(62.5, $after->outputs['qec_exposure']['value'], 1e-9);

        $this->get(route('tools.calculations.print', $after->uuid))
            ->assertOk()
            ->assertSee('۲ تا ۴ ساعت')
            ->assertSee('متوسط: ۶ تا ۱۰ کیلوگرم');
    }

    public function test_the_advisor_offers_qec_beside_the_posture_methods(): void
    {
        $this->get(route('tools.advisor', ['hazard' => 'ergonomics', 'stage' => 'measure']))
            ->assertOk()
            ->assertSee('نظر خود کارگر درباره بار، مدت، نیرو و استرس');
    }
}
