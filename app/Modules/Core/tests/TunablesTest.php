<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Core\Domain\AuditLog;
use App\Modules\Core\Filament\Pages\TunablesPage;
use App\Modules\Core\Services\SettingsRepository;
use App\Modules\Core\Services\Tunables;
use App\Modules\Monetization\Domain\Enums\BillingCycle;
use App\Modules\Monetization\Services\TeamPricing;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * پنل «قیمت‌ها و زمان‌ها»: عدد مدیر روی همان کلید config می‌نشیند، پس ماژول‌ها
 * بی‌تغییر همان عدد را می‌خوانند؛ پیش‌فرض فایل config می‌ماند و هر تغییر در
 * دفتر رویداد ثبت می‌شود.
 */
final class TunablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_modules_declare_their_prices_and_durations(): void
    {
        $keys = array_keys($this->app->make(Tunables::class)->definitions());

        foreach ([
            'monetization.team.tiers.0.unit_price_toman',
            'monetization.team.min_seats',
            'commerce.payout.minimum_toman',
            'consulting.orders.reply_hours',
            'consulting.orders.auto_release_days',
            'consulting.reviews.due_days',
            'webinars.hold_minutes',
            'projects.calibration_warning_days',
        ] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_every_declared_key_exists_in_config_and_its_default_is_allowed(): void
    {
        $tunables = $this->app->make(Tunables::class);

        foreach ($tunables->definitions() as $key => $tunable) {
            $this->assertIsInt(config($key), $key);
            $this->assertTrue($tunable->accepts($tunables->default($key)), $key);
        }
    }

    public function test_a_saved_value_reaches_the_module_and_the_file_default_stays(): void
    {
        $tunables = $this->app->make(Tunables::class);

        $tunables->update(['monetization.team.tiers.0.unit_price_toman' => 200_000], $this->admin()->id);

        $this->assertSame(200_000, $this->app->make(TeamPricing::class)->unitPrice(3)->toman);
        $this->assertSame(250_000, $tunables->default('monetization.team.tiers.0.unit_price_toman'));
        $this->assertSame(200_000 * 3 * 10, $this->app->make(TeamPricing::class)->total(3, BillingCycle::Yearly)->toman);
    }

    public function test_the_saved_value_survives_a_fresh_boot(): void
    {
        $this->app->make(SettingsRepository::class)->set('consulting.orders.reply_hours', 72);
        config(['consulting.orders.reply_hours' => 48]);

        $this->app->make(Tunables::class)->apply();

        $this->assertSame(72, config('consulting.orders.reply_hours'));
    }

    public function test_a_stored_value_out_of_bounds_is_ignored(): void
    {
        // عددی که پیش از سخت‌گیری حدها ذخیره شده نباید مهلت را صفر کند.
        $this->app->make(SettingsRepository::class)->set('consulting.orders.reply_hours', 0);

        $this->app->make(Tunables::class)->apply();

        $this->assertSame(48, config('consulting.orders.reply_hours'));
    }

    public function test_an_out_of_bounds_value_rejects_the_whole_save(): void
    {
        $tunables = $this->app->make(Tunables::class);

        try {
            $tunables->update(['webinars.hold_minutes' => 30, 'commerce.payout.minimum_toman' => 0], $this->admin()->id);
            $this->fail('قیمت صفر پذیرفته شد.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('کمترین مبلغ درخواست تسویه', $exception->getMessage());
        }

        $this->assertSame(20, config('webinars.hold_minutes'));
    }

    public function test_the_panel_page_saves_persian_digits_and_audits_the_change(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(TunablesPage::class)
            ->assertSee('قیمت هر صندلی تیم در ماه')
            ->set('values.consulting__orders__reply_hours', '۷۲')
            ->set('values.commerce__payout__minimum_toman', '۳۰۰٬۰۰۰')
            ->call('save')
            ->assertSet('error', null);

        $this->assertSame(72, config('consulting.orders.reply_hours'));
        $this->assertSame(300_000, config('commerce.payout.minimum_toman'));

        $after = AuditLog::query()->where('action', 'settings.tunable_changed')->pluck('after')->all();
        $this->assertEqualsCanonicalizing([['consulting.orders.reply_hours' => 72], ['commerce.payout.minimum_toman' => 300_000]], $after);
    }

    public function test_the_panel_page_reports_a_non_numeric_value(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(TunablesPage::class)
            ->set('values.webinars__hold_minutes', 'بیست')
            ->call('save')
            ->assertSet('error', '«نگه‌داشتن جای ثبت‌نام تا پایان پرداخت» را فقط با رقم بنویسید.');
    }

    public function test_only_the_super_admin_opens_the_page(): void
    {
        $this->actingAs($this->admin(AdminRole::Finance))
            ->get(route('filament.fbh.pages.tunables'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('filament.fbh.pages.tunables'))
            ->assertOk()
            ->assertSee('کمترین مبلغ درخواست تسویه');
    }

    private function admin(AdminRole $role = AdminRole::Super): User
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
