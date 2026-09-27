<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Contracts\SmsSender;
use App\Models\User;
use App\Modules\Identity\Services\Sms\FakeSmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * خوش‌آمد اولین ورود: یک بار، با نام اختیاری و رفتن به بخشی که کاربر می‌خواهد.
 */
final class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_offers_every_goal_and_a_way_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('identity.welcome'))
            ->assertOk()
            ->assertSee('data-page-help="welcome"', false)
            ->assertSee('پروژه اندازه‌گیری و گزارش')
            ->assertSee('value="skip"', false);
    }

    public function test_picking_a_goal_saves_the_name_and_opens_that_section(): void
    {
        $user = User::factory()->create(['name' => 'کاربر']);

        $this->actingAs($user)
            ->post(route('identity.welcome.store'), ['name' => 'مریم رضایی', 'goal' => 'tools'])
            ->assertRedirect(route('tools.index'));

        $user->refresh();
        $this->assertSame('مریم رضایی', $user->name);
        $this->assertTrue($user->hasCompletedOnboarding());
    }

    public function test_skipping_finishes_onboarding_without_touching_the_name(): void
    {
        $user = User::factory()->create(['name' => 'علی']);

        $this->actingAs($user)
            ->post(route('identity.welcome.store'), ['goal' => 'skip'])
            ->assertRedirect(route('workspace.dashboard'));

        $user->refresh();
        $this->assertSame('علی', $user->name);
        $this->assertTrue($user->hasCompletedOnboarding());
    }

    public function test_an_unknown_goal_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('identity.welcome.store'), ['goal' => 'admin'])
            ->assertSessionHasErrors('goal');

        $this->assertFalse($user->refresh()->hasCompletedOnboarding());
    }

    public function test_only_the_first_sign_in_lands_on_the_welcome_page(): void
    {
        $sms = new FakeSmsSender;
        $this->app->instance(SmsSender::class, $sms);
        User::factory()->onboarded()->create(['mobile' => '09121234567']);

        $this->post(route('identity.login.store'), ['mobile' => '09121234567', 'terms' => '1']);
        preg_match('/(\d+)\s*$/u', $sms->lastMessageTo('09121234567') ?? '', $code);

        $this->post(route('identity.verify.store'), ['code' => $code[1] ?? ''])
            ->assertRedirect(route('identity.profiles'));
    }
}
