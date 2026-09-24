<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Tools\Actions\UpdateToolSettings;
use App\Modules\Tools\Console\SyncToolsCommand;
use App\Modules\Tools\Domain\Tool;
use App\Modules\Tools\Events\FormulaVersionPinned;
use App\Modules\Tools\Events\ToolAvailabilityChanged;
use App\Modules\Tools\Events\ToolReviewed;
use App\Modules\Tools\Filament\Pages\ToolsPage;
use App\Modules\Tools\Services\ToolCatalog;
use App\Modules\Tools\Services\ToolNotFound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * اداره ابزارها از پنل مدیریت.
 */
final class ToolAdminTest extends TestCase
{
    use RefreshDatabase;

    private UpdateToolSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = $this->app->make(UpdateToolSettings::class);
    }

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    public function test_only_content_and_super_admins_manage_tools(): void
    {
        // ابزار محتوای علمی است، نه مالی و نه کاریابی.
        $this->assertTrue($this->adminWith(AdminRole::Content)->can(ToolsPage::ABILITY));
        $this->assertTrue($this->adminWith(AdminRole::Super)->can(ToolsPage::ABILITY));
        $this->assertFalse($this->adminWith(AdminRole::Finance)->can(ToolsPage::ABILITY));
        $this->assertFalse($this->adminWith(AdminRole::Jobs)->can(ToolsPage::ABILITY));
        $this->assertFalse(User::factory()->create()->can(ToolsPage::ABILITY));
    }

    public function test_disabling_a_tool_hides_its_page(): void
    {
        $this->get(route('tools.show', 'wbgt-indoor'))->assertOk();

        $this->settings->setAvailability('wbgt-indoor', false, null);

        $this->get(route('tools.show', 'wbgt-indoor'))->assertNotFound();
        $this->get(route('tools.index'))->assertOk()->assertDontSee('شاخص WBGT — بدون تابش خورشید');
    }

    public function test_a_disabled_tool_refuses_a_direct_post_too(): void
    {
        $this->settings->setAvailability('wbgt-indoor', false, null);

        $this->post(route('tools.calculate', 'wbgt-indoor'), [
            'natural_wet_bulb' => '25',
            'globe' => '35',
        ])->assertNotFound();
    }

    public function test_every_admin_change_publishes_an_auditable_event(): void
    {
        Event::fake([ToolAvailabilityChanged::class, FormulaVersionPinned::class, ToolReviewed::class]);

        // پس از Event::fake دوباره ساخته می‌شود: کنش، Dispatcher را از سازنده
        // می‌گیرد و نمونه‌ای که در setUp ساخته شده هنوز Dispatcher واقعی را دارد.
        $settings = $this->app->make(UpdateToolSettings::class);
        $actor = (int) $this->adminWith(AdminRole::Content)->getKey();

        $settings->setAvailability('wbgt-indoor', false, $actor);
        $settings->pinVersion('wbgt-indoor', '1.0.0', $actor);
        $settings->markReviewed('wbgt-indoor', $actor);

        Event::assertDispatched(ToolAvailabilityChanged::class);
        Event::assertDispatched(FormulaVersionPinned::class);
        Event::assertDispatched(ToolReviewed::class);
    }

    public function test_an_admin_change_is_written_to_the_audit_log(): void
    {
        // بدون Event::fake — مسیر واقعی تا دفتر رویداد Core.
        $actor = (int) $this->adminWith(AdminRole::Content)->getKey();

        $this->settings->setAvailability('wbgt-indoor', false, $actor);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tools.availability_changed',
            'subject_id' => 'wbgt-indoor',
            'actor_id' => $actor,
        ]);
    }

    public function test_an_unchanged_setting_publishes_nothing(): void
    {
        // ردیف دفتر رویداد برای «هیچ اتفاقی نیفتاد» دفتر را بی‌ارزش می‌کند.
        $this->settings->setAvailability('wbgt-indoor', true, null);

        Event::fake([ToolAvailabilityChanged::class]);

        $this->app->make(UpdateToolSettings::class)->setAvailability('wbgt-indoor', true, null);

        Event::assertNotDispatched(ToolAvailabilityChanged::class);
    }

    public function test_a_version_that_does_not_exist_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->settings->pinVersion('wbgt-indoor', '3.0.0', null);
    }

    public function test_an_unknown_tool_is_rejected(): void
    {
        $this->expectException(ToolNotFound::class);

        $this->settings->setAvailability('nothing-like-this', false, null);
    }

    public function test_marking_a_review_clears_the_overdue_state(): void
    {
        $catalog = $this->app->make(ToolCatalog::class);

        // هرگز بازبینی‌نشده با «عقب‌افتاده» یکی نیست.
        $this->assertSame('not_reviewed', $catalog->resolve('wbgt-indoor')->availability->value);

        $this->settings->markReviewed('wbgt-indoor', null);

        $this->assertSame('available', $catalog->resolve('wbgt-indoor')->availability->value);
    }

    public function test_a_review_older_than_the_interval_is_overdue_again(): void
    {
        $this->settings->markReviewed('wbgt-indoor', null);

        Tool::query()->whereKey('wbgt-indoor')->update([
            'reviewed_at' => now()->subDays((int) config('tools.review_interval_days') + 1),
        ]);

        $catalog = $this->app->make(ToolCatalog::class);
        $catalog->forget();

        $this->assertSame('review_overdue', $catalog->resolve('wbgt-indoor')->availability->value);
    }

    public function test_the_affected_calculation_count_is_reported_before_a_version_change(): void
    {
        $this->assertSame(0, $this->settings->affectedCalculations('wbgt-indoor'));

        $this->actingAs(User::factory()->create())
            ->post(route('tools.calculations.store', 'wbgt-indoor'), [
                'natural_wet_bulb' => '25',
                'globe' => '35',
            ])->assertRedirect();

        $this->assertSame(1, $this->settings->affectedCalculations('wbgt-indoor'));
    }

    public function test_sync_creates_rows_for_new_tools_and_leaves_existing_ones_alone(): void
    {
        $this->settings->setAvailability('wbgt-indoor', false, null);

        Tool::query()->whereKeyNot('wbgt-indoor')->delete();

        $this->artisan(SyncToolsCommand::class)->assertSuccessful();

        $catalog = $this->app->make(ToolCatalog::class);
        $catalog->forget();

        // مدیری که ابزاری را خاموش کرده، نباید با استقرار بعدی دوباره روشنش ببیند.
        $this->assertFalse($catalog->resolve('wbgt-indoor')->usable());
        $this->assertSame(count($catalog->definitions()), Tool::query()->count());
    }

    public function test_the_admin_page_is_reachable_for_a_content_admin(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content))
            ->get('/'.config('admin.path').'/tools')
            ->assertOk()
            ->assertSee('ابزارها و نسخه فرمول');
    }

    public function test_the_admin_page_is_closed_to_a_finance_admin(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get('/'.config('admin.path').'/tools')
            ->assertForbidden();
    }
}
