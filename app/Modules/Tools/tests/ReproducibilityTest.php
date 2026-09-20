<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Models\User;
use App\Modules\Tools\Actions\ReplayCalculation;
use App\Modules\Tools\Actions\RunCalculation;
use App\Modules\Tools\Actions\SaveCalculation;
use App\Modules\Tools\Actions\UpdateToolSettings;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Services\ToolCatalog;
use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\FormulaRegistry;
use Farabehdasht\CalcEngine\Formulas\DefaultFormulas;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputSet;
use Farabehdasht\CalcEngine\Outcome;
use Farabehdasht\CalcEngine\Reference;
use Farabehdasht\CalcEngine\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۷: «محاسبه ذخیره‌شده با نسخه فرمول لحظه ثبت قابل بازتولید است.»
 *
 * تست سبزی که فقط یک محاسبه را دوباره اجرا کند چیزی ثابت نمی‌کند؛ وقتی
 * نسخه‌ای نباشد، هر دو اجرا طبیعتاً یکی‌اند. برای همین این‌جا **نسخه دوم واقعاً
 * ثبت می‌شود** و بعد بازتولید بررسی می‌شود.
 */
final class ReproducibilityTest extends TestCase
{
    use RefreshDatabase;

    private const INPUTS = ['natural_wet_bulb' => 25.0, 'globe' => 35.0];

    private function save(): SavedCalculation
    {
        $tool = $this->app->make(ToolCatalog::class)->resolve('wbgt-indoor');

        return $this->app->make(SaveCalculation::class)->handle(
            User::factory()->create(),
            $tool,
            $this->app->make(RunCalculation::class)->handle($tool, self::INPUTS),
            'ایستگاه ۳',
        );
    }

    /**
     * نسخه دوم را در موتور ثبت می‌کند و کانتینر را تازه می‌کند.
     */
    private function registerSecondVersion(): void
    {
        $this->app->singleton(FormulaRegistry::class, function (): FormulaRegistry {
            $registry = DefaultFormulas::registry();
            $registry->register($this->secondVersion());

            return $registry;
        });

        $this->app->forgetInstance(Engine::class);
        $this->app->forgetInstance(ToolCatalog::class);
    }

    /**
     * نسخه دوم ساختگی `wbgt-indoor`.
     *
     * عمداً عدد متفاوتی می‌دهد (۰٫۵/۰٫۵ به‌جای ۰٫۷/۰٫۳) تا اگر بازتولید سراغ
     * نسخه روز برود، تست قرمز شود. نسخه‌اش ۹٫۹٫۹ است تا هیچ‌وقت با نسخه واقعی
     * آینده اشتباه نشود.
     */
    private function secondVersion(): Formula
    {
        return new class implements Formula
        {
            public function id(): string
            {
                return 'wbgt-indoor';
            }

            public function version(): string
            {
                return '9.9.9';
            }

            public function title(): string
            {
                return 'شاخص WBGT — نسخه آزمایشی';
            }

            public function reference(): Reference
            {
                return new Reference('آزمایشی', 'فرابهداشت', 2026, 'WBGT = 0.5 × Tnw + 0.5 × Tg');
            }

            public function limitations(): array
            {
                return ['این رابطه فقط برای تست است.'];
            }

            public function inputs(): array
            {
                return [
                    'natural_wet_bulb' => InputDefinition::single('natural_wet_bulb', 'دمای تر طبیعی', Unit::Celsius, -50.0, 100.0),
                    'globe' => InputDefinition::single('globe', 'دمای گوی', Unit::Celsius, -50.0, 150.0),
                ];
            }

            public function outputs(): array
            {
                return ['wbgt' => Unit::Celsius];
            }

            public function compute(InputSet $inputs): Outcome
            {
                return new Outcome([
                    'wbgt' => 0.5 * $inputs->value('natural_wet_bulb') + 0.5 * $inputs->value('globe'),
                ]);
            }
        };
    }

    public function test_a_saved_calculation_records_the_version_that_produced_it(): void
    {
        $saved = $this->save();

        $this->assertSame('wbgt-indoor', $saved->formula_id);
        $this->assertSame('1.0.0', $saved->formula_version);
        $this->assertEqualsWithDelta(28.0, $saved->outputs['wbgt']['value'], 1e-9);
    }

    public function test_a_replay_reproduces_the_stored_outputs(): void
    {
        $saved = $this->save();

        $this->assertTrue($this->app->make(ReplayCalculation::class)->matches($saved));
    }

    public function test_a_replay_uses_the_stored_version_even_when_a_newer_one_exists(): void
    {
        $saved = $this->save();

        $this->registerSecondVersion();

        // نسخه ۹٫۹٫۹ با همان ورودی عدد ۳۰ می‌دهد، نه ۲۸. اگر بازتولید نسخه روز
        // را بردارد، این تست قرمز می‌شود — و همین دقیقاً معیار پذیرش است.
        $replayed = $this->app->make(ReplayCalculation::class)->handle($saved);

        $this->assertSame('1.0.0', $replayed->formulaVersion);
        $this->assertEqualsWithDelta(28.0, $replayed->output('wbgt')->value, 1e-9);
        $this->assertTrue($this->app->make(ReplayCalculation::class)->matches($saved));
    }

    public function test_a_new_calculation_uses_the_newer_version_while_the_old_one_stays_put(): void
    {
        $saved = $this->save();

        $this->registerSecondVersion();

        $tool = $this->app->make(ToolCatalog::class)->resolve('wbgt-indoor');
        $fresh = $this->app->make(RunCalculation::class)->handle($tool, self::INPUTS);

        $this->assertSame('9.9.9', $fresh->formulaVersion);
        $this->assertEqualsWithDelta(30.0, $fresh->output('wbgt')->value, 1e-9);

        // ردیف قدیمی دست‌نخورده است.
        $this->assertEqualsWithDelta(28.0, $saved->fresh()?->outputs['wbgt']['value'], 1e-9);
    }

    public function test_pinning_an_older_version_makes_new_calculations_use_it(): void
    {
        $this->registerSecondVersion();

        $this->app->make(UpdateToolSettings::class)->pinVersion('wbgt-indoor', '1.0.0', null);

        $tool = $this->app->make(ToolCatalog::class)->resolve('wbgt-indoor');

        $this->assertTrue($tool->versionPinned);
        $this->assertSame('1.0.0', $tool->version());

        $calculation = $this->app->make(RunCalculation::class)->handle($tool, self::INPUTS);

        $this->assertEqualsWithDelta(28.0, $calculation->output('wbgt')->value, 1e-9);
    }

    public function test_a_pinned_version_that_vanished_falls_back_to_the_latest(): void
    {
        // سنجاق به نسخه‌ای که دیگر در کد نیست نباید صفحه را بترکاند.
        $this->registerSecondVersion();
        $this->app->make(UpdateToolSettings::class)->pinVersion('wbgt-indoor', '9.9.9', null);

        $this->app->singleton(FormulaRegistry::class, static fn (): FormulaRegistry => DefaultFormulas::registry());
        $this->app->forgetInstance(Engine::class);
        $this->app->forgetInstance(ToolCatalog::class);

        $tool = $this->app->make(ToolCatalog::class)->resolve('wbgt-indoor');

        $this->assertFalse($tool->versionPinned);
        $this->assertSame('1.0.0', $tool->version());
    }
}
