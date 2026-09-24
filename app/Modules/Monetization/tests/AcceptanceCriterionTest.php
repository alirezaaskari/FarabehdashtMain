<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Contracts\EntitlementGate;
use App\Models\User;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Enums\ShutdownPolicy;
use App\Modules\Projects\Domain\Project;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Support\Entitlement\EntitlementReason;
use App\Support\Entitlement\Feature;
use App\Support\Entitlement\OpenGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۱۴:
 * «خاموش‌کردن اشتراک بدون تغییر کد هیچ صفحه‌ای، رفتار کل سایت را عوض می‌کند.»
 *
 * هر تست این فایل همان یک جمله را از یک زاویه می‌سنجد. هیچ‌کدام کدی را عوض
 * نمی‌کنند؛ فقط کلید را می‌زنند.
 */
final class AcceptanceCriterionTest extends TestCase
{
    use RefreshDatabase;

    private const INPUTS = ['natural_wet_bulb' => '25', 'globe' => '35'];

    public function test_turning_the_subscription_off_lifts_every_quota_on_the_whole_site(): void
    {
        $user = User::factory()->create();

        // با کلید روشن: سقف واقعی است.
        for ($i = 0; $i < 5; $i++) {
            $this->save($user);
        }

        $this->save($user);
        $this->assertSame(5, SavedCalculation::query()->forUser((int) $user->getKey())->count());

        $this->createProject($user, 'پروژه اول');
        $this->createProject($user, 'پروژه دوم');
        $this->assertSame(1, Project::query()->forUser((int) $user->getKey())->count());

        // فقط کلید زده می‌شود — هیچ کدی عوض نمی‌شود.
        $this->turnOff();

        $this->save($user);
        $this->save($user);
        $this->assertSame(7, SavedCalculation::query()->forUser((int) $user->getKey())->count());

        $this->createProject($user, 'پروژه دوم');
        $this->createProject($user, 'پروژه سوم');
        $this->assertSame(3, Project::query()->forUser((int) $user->getKey())->count());
    }

    public function test_the_subscription_page_redirects_instead_of_returning_404(): void
    {
        $this->get(route('monetization.plans'))->assertOk();

        $this->turnOff();

        $this->get(route('monetization.plans'))->assertRedirect(route('home'));
        $this->get(route('monetization.upgrade'))->assertRedirect(route('home'));
    }

    public function test_the_subscription_row_leaves_the_site_navigation(): void
    {
        $this->get(route('tools.index'))->assertOk()->assertSee('اشتراک');

        $this->turnOff();

        $this->get(route('tools.index'))->assertOk()->assertDontSee('اشتراک');
    }

    /**
     * برداشتن کل ماژول، همان اثر را دارد و از همان یک در می‌گذرد.
     *
     * صفحه‌ها `OpenGate` و `EntitlementResolver` را از هم تشخیص نمی‌دهند؛
     * برای همین است که هیچ‌کدامشان شاخه «اگر ماژول درآمدزایی نبود» ندارند.
     */
    public function test_without_the_module_at_all_every_feature_is_open(): void
    {
        $this->app->instance(EntitlementGate::class, new OpenGate);

        $user = User::factory()->create();

        for ($i = 0; $i < 8; $i++) {
            $this->save($user);
        }

        $this->assertSame(8, SavedCalculation::query()->forUser((int) $user->getKey())->count());

        foreach (Feature::cases() as $feature) {
            $decision = (new OpenGate)->decide(null, $feature);

            $this->assertTrue($decision->allowed(), $feature->value);
            $this->assertSame(EntitlementReason::StreamDisabled, $decision->reason);
        }
    }

    private function turnOff(): void
    {
        $this->app->make(ToggleRevenueStream::class)->handle(
            RevenueStream::ProSubscription,
            false,
            ShutdownPolicy::RunToEnd,
        );
    }

    private function save(User $user): void
    {
        $this->actingAs($user)->post(
            route('tools.calculations.store', 'wbgt-indoor'),
            [...self::INPUTS, 'label' => 'ایستگاه آزمایشی'],
        );
    }

    private function createProject(User $user, string $title): void
    {
        $this->actingAs($user)->post(route('projects.store'), ['title' => $title]);
    }
}
