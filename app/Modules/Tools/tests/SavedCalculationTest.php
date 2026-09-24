<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Models\User;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Events\CalculationSaved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

/**
 * ذخیره، نمایش و تغییرناپذیری محاسبه.
 */
final class SavedCalculationTest extends TestCase
{
    use RefreshDatabase;

    private const INPUTS = ['natural_wet_bulb' => '25', 'globe' => '35'];

    private function store(User $user, array $inputs = self::INPUTS, ?string $label = 'ایستگاه ۳'): SavedCalculation
    {
        $this->actingAs($user)
            ->post(route('tools.calculations.store', 'wbgt-indoor'), [...$inputs, 'label' => $label])
            ->assertRedirect();

        $saved = SavedCalculation::query()->latest('id')->first();

        $this->assertNotNull($saved);

        return $saved;
    }

    public function test_a_guest_cannot_save(): void
    {
        $this->post(route('tools.calculations.store', 'wbgt-indoor'), self::INPUTS)
            ->assertRedirect(route('login'));

        $this->assertSame(0, SavedCalculation::query()->count());
    }

    public function test_saving_stores_the_version_and_the_inputs(): void
    {
        $saved = $this->store(User::factory()->create());

        $this->assertSame('wbgt-indoor', $saved->tool_slug);
        $this->assertSame('1.0.0', $saved->formula_version);
        $this->assertSame('ایستگاه ۳', $saved->label);
        $this->assertEqualsWithDelta(25.0, $saved->inputs['natural_wet_bulb']['value'], 1e-9);
        $this->assertEqualsWithDelta(28.0, $saved->outputs['wbgt']['value'], 1e-9);
    }

    public function test_saving_publishes_an_auditable_event_without_the_measurements(): void
    {
        Event::fake([CalculationSaved::class]);

        $this->store(User::factory()->create());

        Event::assertDispatched(CalculationSaved::class, function (CalculationSaved $event): bool {
            $entry = $event->auditEntry();

            // داده میدانی می‌تواند محرمانه کارفرما باشد؛ دفتر رویداد جای
            // نگه‌داشتنش نیست.
            $this->assertSame(['tool', 'formula', 'version'], array_keys($entry->after));
            $this->assertSame('tools.calculation_saved', $entry->action);

            return true;
        });
    }

    public function test_invalid_input_does_not_save_anything(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('tools.calculations.store', 'wbgt-indoor'), ['natural_wet_bulb' => 'نود'])
            ->assertOk()
            ->assertSee('باید عدد باشد');

        $this->assertSame(0, SavedCalculation::query()->count());
    }

    public function test_a_saved_calculation_can_never_be_edited(): void
    {
        $saved = $this->store(User::factory()->create());

        $this->expectException(RuntimeException::class);

        $saved->update(['label' => 'چیز دیگر']);
    }

    public function test_a_saved_calculation_can_never_be_deleted(): void
    {
        $saved = $this->store(User::factory()->create());

        $this->expectException(RuntimeException::class);

        $saved->delete();
    }

    public function test_the_owner_sees_the_calculation_and_its_print_version(): void
    {
        $user = User::factory()->create();
        $saved = $this->store($user);

        $this->actingAs($user)->get(route('tools.calculations.show', $saved->uuid))
            ->assertOk()
            ->assertSee('بازتولید شد')
            ->assertSee('ایستگاه ۳');

        $this->actingAs($user)->get(route('tools.calculations.print', $saved->uuid))
            ->assertOk()
            ->assertSee('WBGT = 0.7 × Tnw + 0.3 × Tg', escape: false)
            ->assertSee('این خروجی یک محاسبه عددی است', escape: false);
    }

    public function test_another_users_calculation_is_a_404_not_a_403(): void
    {
        // پاسخ متفاوت یعنی اعلام اینکه این شناسه وجود دارد.
        $saved = $this->store(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('tools.calculations.show', $saved->uuid))
            ->assertNotFound();
    }

    public function test_the_history_page_lists_only_my_calculations(): void
    {
        $mine = User::factory()->create();
        $this->store($mine, label: 'مال من');
        $this->store(User::factory()->create(), label: 'مال دیگری');

        $this->actingAs($mine)->get(route('tools.calculations.index'))
            ->assertOk()
            ->assertSee('مال من')
            ->assertDontSee('مال دیگری');
    }

    public function test_the_tools_page_lists_my_recent_tools_first(): void
    {
        $mine = User::factory()->create();
        $this->store($mine);

        $this->actingAs($mine)->get(route('tools.index'))
            ->assertOk()
            ->assertSeeInOrder(['اخیراً استفاده‌شده', 'شاخص WBGT', 'استرس گرمایی']);
    }

    public function test_the_recent_row_is_hidden_without_saved_calculations(): void
    {
        $this->get(route('tools.index'))->assertOk()->assertDontSee('اخیراً استفاده‌شده');

        // محاسبه کاربر دیگر در فهرست من نمی‌آید.
        $this->store(User::factory()->create());
        $this->actingAs(User::factory()->create())->get(route('tools.index'))
            ->assertOk()
            ->assertDontSee('اخیراً استفاده‌شده');
    }

    public function test_an_empty_history_shows_the_empty_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('tools.calculations.index'))
            ->assertOk()
            ->assertSee('هنوز محاسبه‌ای ذخیره نکرده‌اید');
    }

    public function test_a_list_input_survives_the_round_trip_to_the_print_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tools.calculations.store', 'sound-pressure-sum'), [
            'levels' => ['90', '90'],
            'label' => 'دو منبع',
        ])->assertRedirect();

        $saved = SavedCalculation::query()->latest('id')->firstOrFail();

        $this->actingAs($user)->get(route('tools.calculations.print', $saved->uuid))
            ->assertOk()
            ->assertSee('ترازهای واردشده')
            ->assertSee('93.0103');
    }
}
