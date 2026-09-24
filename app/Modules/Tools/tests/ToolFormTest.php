<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Modules\Tools\Services\ToolInputCaster;
use Farabehdasht\CalcEngine\Formulas\Noise\SoundPressureSumV1;
use Farabehdasht\CalcEngine\Formulas\Wbgt\WbgtIndoorV1;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * رفتار فرم ابزار: چه چیزی عدد می‌شود، چه چیزی خطا، و چه چیزی نادیده گرفته می‌شود.
 */
final class ToolFormTest extends TestCase
{
    use RefreshDatabase;

    private ToolInputCaster $caster;

    protected function setUp(): void
    {
        parent::setUp();

        $this->caster = $this->app->make(ToolInputCaster::class);
    }

    public function test_persian_digits_are_accepted(): void
    {
        // کاربر میدانی با کیبورد فارسی تایپ می‌کند؛ رد کردن «۲۵» آزار بی‌دلیل است.
        $this->post(route('tools.calculate', 'wbgt-indoor'), [
            'natural_wet_bulb' => '۲۵',
            'globe' => '۳۵',
        ])->assertOk()->assertSee('28');
    }

    public function test_a_persian_decimal_separator_is_accepted(): void
    {
        $cast = $this->caster->cast((new WbgtIndoorV1)->inputs(), [
            'natural_wet_bulb' => '۲۵٫۵',
            'globe' => '35',
        ]);

        $this->assertEqualsWithDelta(25.5, $cast['natural_wet_bulb'], 1e-9);
    }

    public function test_a_word_is_never_silently_turned_into_zero(): void
    {
        // (float) 'سه' در PHP صفر است. همین یک تبدیل خاموش می‌تواند یک گزارش
        // مواجهه را بی‌سروصدا غلط کند.
        $cast = $this->caster->cast((new WbgtIndoorV1)->inputs(), [
            'natural_wet_bulb' => 'سه',
            'globe' => '35',
        ]);

        $this->assertSame('سه', $cast['natural_wet_bulb']);
    }

    public function test_an_empty_field_is_dropped_so_the_engine_calls_it_missing(): void
    {
        $cast = $this->caster->cast((new WbgtIndoorV1)->inputs(), [
            'natural_wet_bulb' => '  ',
            'globe' => '35',
        ]);

        $this->assertArrayNotHasKey('natural_wet_bulb', $cast);
    }

    public function test_empty_rows_of_a_list_are_ignored(): void
    {
        $cast = $this->caster->cast((new SoundPressureSumV1)->inputs(), [
            'levels' => ['90', '', '85', '   '],
        ]);

        $this->assertSame([90.0, 85.0], $cast['levels']);
    }

    public function test_a_missing_field_reports_that_it_is_required(): void
    {
        $this->post(route('tools.calculate', 'wbgt-indoor'), ['natural_wet_bulb' => '25'])
            ->assertOk()
            ->assertSee('الزامی است');
    }

    public function test_a_value_outside_the_measurable_range_is_rejected(): void
    {
        $this->post(route('tools.calculate', 'wbgt-indoor'), [
            'natural_wet_bulb' => '500',
            'globe' => '35',
        ])->assertOk()->assertSee('باید بین');
    }

    public function test_mismatched_paired_lists_are_reported_on_the_form(): void
    {
        $this->post(route('tools.calculate', 'equivalent-continuous-level'), [
            'levels' => ['90', '80'],
            'durations' => ['240'],
        ])->assertOk()->assertSee('یکی نیست');
    }

    public function test_a_bad_row_names_its_position_with_persian_digits(): void
    {
        $this->post(route('tools.calculate', 'sound-pressure-sum'), [
            'levels' => ['90', '900'],
        ])->assertOk()->assertSee('ردیف ۲');
    }

    public function test_the_result_always_carries_the_disclaimer_and_the_version(): void
    {
        $this->post(route('tools.calculate', 'wbgt-indoor'), [
            'natural_wet_bulb' => '25',
            'globe' => '35',
        ])->assertOk()
            ->assertSee('تشخیص پزشکی، تأیید ایمنی یا انطباق قانونی محسوب نمی‌شود', escape: false)
            ->assertSee('wbgt-indoor@1.0.0');
    }

    public function test_the_interpretation_belongs_to_the_tools_own_category(): void
    {
        // پیش‌تر متن گرما («بار متابولیکی») زیر نتیجه صدا هم می‌آمد.
        $this->post(route('tools.calculate', 'sound-pressure-sum'), [
            'levels' => ['90', '85'],
        ])->assertOk()
            ->assertSee('نرخ تبادل')
            ->assertDontSee('بار متابولیکی');
    }

    public function test_a_guest_is_offered_login_instead_of_a_dead_end(): void
    {
        $this->post(route('tools.calculate', 'wbgt-indoor'), [
            'natural_wet_bulb' => '25',
            'globe' => '35',
        ])->assertOk()->assertSee('برای ذخیره این محاسبه باید وارد شوید');
    }

    public function test_field_mode_keeps_only_the_form_and_the_result(): void
    {
        $this->get(route('tools.show', ['wbgt-indoor', 'field' => 1]))
            ->assertOk()
            ->assertSee('data-field-mode', escape: false)
            ->assertSee('خروج از حالت میدانی')
            ->assertSee(route('tools.calculate', ['wbgt-indoor', 'field' => 1]), escape: false)
            ->assertDontSee('فرمول به‌کاررفته')
            ->assertDontSee('درباره این ابزار');

        $this->get(route('tools.show', 'wbgt-indoor'))
            ->assertSee('حالت میدانی')
            ->assertSee('فرمول به‌کاررفته')
            ->assertDontSee('data-field-mode', escape: false);
    }

    public function test_field_mode_survives_the_calculation_and_never_reaches_the_formula(): void
    {
        $this->post(route('tools.calculate', ['wbgt-indoor', 'field' => 1]), [
            'natural_wet_bulb' => '25',
            'globe' => '35',
        ])->assertOk()
            ->assertSee('data-field-mode', escape: false)
            ->assertSee('تشخیص پزشکی، تأیید ایمنی یا انطباق قانونی محسوب نمی‌شود', escape: false)
            ->assertDontSee('محاسبه انجام نشد')
            ->assertSee('ذخیره')
            ->assertDontSee('name="field"', escape: false);
    }

    public function test_default_values_are_pre_filled(): void
    {
        // فشار مرجع را کسی از بر نیست؛ ولی باید بتواند عوضش کند.
        $this->get(route('tools.show', 'ppm-to-mass-concentration'))
            ->assertOk()
            ->assertSee('101.325');
    }

    public function test_a_short_window_tool_explains_its_window(): void
    {
        $this->get(route('tools.show', 'stel-ppm'))
            ->assertOk()
            ->assertSee('دقیقه‌ای در نظر گرفته شده است');
    }

    public function test_the_form_can_be_prefilled_from_the_address(): void
    {
        // صفحه ماده جرم مولکولی را در نشانی می‌فرستد؛ پارامتر ناشناخته دور ریخته می‌شود.
        $this->get(route('tools.show', ['slug' => 'ppm-to-mass-concentration', 'molecular_weight' => '92.14', 'evil' => 'x']))
            ->assertOk()
            ->assertSee('value="92.14"', escape: false)
            ->assertDontSee('value="x"', escape: false);
    }

    public function test_the_two_wbgt_tools_switch_to_each_other(): void
    {
        $this->get(route('tools.show', 'wbgt-indoor'))
            ->assertOk()
            ->assertSee('aria-label="نوع محیط"', escape: false)
            ->assertSee(route('tools.show', 'wbgt-outdoor'), escape: false);

        $this->get(route('tools.show', 'wbgt-outdoor'))
            ->assertSee(route('tools.show', 'wbgt-indoor'), escape: false);
    }

    public function test_on_phones_the_result_comes_before_the_formula_box(): void
    {
        // روی موبایل ترتیب DOM ترتیب صفحه است؛ نتیجه نباید زیر جعبه رابطه گم شود.
        $html = $this->get(route('tools.show', 'wbgt-indoor'))->assertOk()->getContent();

        $this->assertLessThan(strpos((string) $html, 'فرمول به‌کاررفته'), strpos((string) $html, 'id="result"'));
    }
}
