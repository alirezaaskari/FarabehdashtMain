<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Contracts\CalculationReferences;
use App\Models\User;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Events\CalculationRemoved;
use App\Modules\Tools\Services\SavedCalculationQuota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * حذف محاسبه ذخیره‌شده — بی‌ارجاع پاک می‌شود، ارجاع‌دار بایگانی.
 */
final class DeleteSavedCalculationTest extends TestCase
{
    use RefreshDatabase;

    private const INPUTS = ['natural_wet_bulb' => '25', 'globe' => '35'];

    public function test_an_unreferenced_calculation_is_really_deleted(): void
    {
        Event::fake([CalculationRemoved::class]);
        $user = User::factory()->create();
        $saved = $this->store($user);

        $this->actingAs($user)
            ->delete(route('tools.calculations.destroy', $saved->uuid))
            ->assertRedirect(route('tools.calculations.index'))
            ->assertSessionHas('status', 'محاسبه حذف شد.');

        $this->assertSame(0, SavedCalculation::query()->count());

        Event::assertDispatched(CalculationRemoved::class, static function (CalculationRemoved $event) use ($saved): bool {
            $entry = $event->auditEntry();

            return $entry->subjectId === $saved->uuid && $entry->after === ['outcome' => 'deleted'];
        });
    }

    public function test_a_referenced_calculation_is_archived_and_leaves_the_list_and_the_quota(): void
    {
        $this->app->instance('test.always-referenced', new class implements CalculationReferences
        {
            public function references(string $calculationUuid): bool
            {
                return true;
            }
        });
        $this->app->tag(['test.always-referenced'], CalculationReferences::TAG);

        $user = User::factory()->create();
        $saved = $this->store($user, 'ایستگاه بایگانی');
        $this->store($user, 'ایستگاه مانده');

        $this->actingAs($user)->delete(route('tools.calculations.destroy', $saved->uuid))->assertRedirect();

        $this->assertNotNull($saved->refresh()->archived_at);
        $this->assertSame(1, $this->app->make(SavedCalculationQuota::class)->countFor($user));

        $this->get(route('tools.calculations.index'))
            ->assertOk()
            ->assertSee('ایستگاه مانده')
            ->assertDontSee('ایستگاه بایگانی')
            ->assertSee('در پروژه یا گزارشی به کار رفته');

        // پیوند مستقیم از پروژه یا گزارش هنوز باز می‌شود.
        $this->get(route('tools.calculations.show', $saved->uuid))->assertOk();
    }

    public function test_someone_elses_calculation_cannot_be_deleted(): void
    {
        $saved = $this->store(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->delete(route('tools.calculations.destroy', $saved->uuid))
            ->assertNotFound();

        $this->assertSame(1, SavedCalculation::query()->count());
    }

    public function test_the_calculation_page_offers_the_delete_button(): void
    {
        $user = User::factory()->create();
        $saved = $this->store($user);

        $this->actingAs($user)->get(route('tools.calculations.show', $saved->uuid))
            ->assertOk()
            ->assertSee(route('tools.calculations.destroy', $saved->uuid))
            ->assertSee('بله، حذف شود');
    }

    private function store(User $user, string $label = 'ایستگاه ۳'): SavedCalculation
    {
        $this->actingAs($user)
            ->post(route('tools.calculations.store', 'wbgt-indoor'), [...self::INPUTS, 'label' => $label])
            ->assertRedirect();

        return SavedCalculation::query()->latest('id')->firstOrFail();
    }
}
