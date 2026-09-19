<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Modules\Core\Domain\Setting;
use App\Modules\Core\Services\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تنظیماتی که مدیر بدون استقرار تازه عوضشان می‌کند.
 */
final class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private SettingsRepository $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = $this->app->make(SettingsRepository::class);
        $this->settings->forget();
    }

    public function test_a_missing_key_falls_back_to_the_default(): void
    {
        // استقرار تازه قبل از پرشدن جدول تنظیمات بالا می‌آید؛ نبودن کلید نباید خطا بدهد.
        $this->assertSame('پیش‌فرض', $this->settings->get('nothing.here', 'پیش‌فرض'));
        $this->assertFalse($this->settings->has('nothing.here'));
    }

    public function test_the_stored_type_survives_the_round_trip(): void
    {
        $this->settings->set('monetization.subscription_enabled', false);
        $this->settings->set('commission.project_percent', 10);
        $this->settings->set('home.featured_tools', ['noise', 'ergonomics']);

        $this->assertFalse($this->settings->get('monetization.subscription_enabled'));
        $this->assertSame(10, $this->settings->get('commission.project_percent'));
        $this->assertSame(['noise', 'ergonomics'], $this->settings->get('home.featured_tools'));
    }

    public function test_a_disabled_revenue_stream_never_reads_as_enabled(): void
    {
        // این دقیقاً همان جایی است که تنظیم رشته‌ای «false» فاجعه می‌سازد.
        $this->settings->set('monetization.subscription_enabled', false);

        $this->assertFalse($this->settings->boolean('monetization.subscription_enabled', true));
    }

    public function test_integers_are_read_as_integers(): void
    {
        $this->settings->set('commission.project_percent', 10);

        $this->assertSame(10, $this->settings->integer('commission.project_percent'));
        $this->assertSame(7, $this->settings->integer('commission.missing', 7));
    }

    public function test_writing_again_updates_the_same_row(): void
    {
        $this->settings->set('commission.project_percent', 10, 'money', 'کمیسیون پروژه');
        $this->settings->set('commission.project_percent', 12);

        $this->assertSame(1, Setting::query()->count());
        $this->assertSame(12, $this->settings->get('commission.project_percent'));

        $row = Setting::query()->sole();
        $this->assertSame('money', $row->group, 'گروه و توضیح نباید با یک نوشتن ساده پاک شوند.');
        $this->assertSame('کمیسیون پروژه', $row->description);
    }

    public function test_a_new_key_without_a_group_lands_in_the_general_group(): void
    {
        $this->settings->set('a.key', 'مقدار');

        $this->assertSame('general', Setting::query()->sole()->group);
    }

    public function test_a_change_is_visible_immediately_even_though_settings_are_cached(): void
    {
        $this->settings->set('a.key', 'اول');
        $this->assertSame('اول', $this->settings->get('a.key'));

        $this->settings->set('a.key', 'دوم');
        $this->assertSame('دوم', $this->settings->get('a.key'));
    }
}
