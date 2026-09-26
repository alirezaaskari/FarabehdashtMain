<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Bundles\Domain\Bundle;
use App\Modules\Bundles\Domain\Enums\BundleStatus;
use App\Modules\Bundles\Filament\Pages\BundlesPage;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class BundlesPageTest extends TestCase
{
    use BundleFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('fbh'));
    }

    public function test_the_admin_builds_and_publishes_a_bundle(): void
    {
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Super);

        $existing = $this->bundle(publish: false);

        Livewire::actingAs($admin->fresh())->test(BundlesPage::class)
            ->assertSee($this->product->title)
            ->set('form.title', 'بسته دوم')
            ->set('form.slug', 'second-kit')
            ->set('form.description', 'فایل و یک ماه حرفه‌ای.')
            ->set('form.price', '۳۰۰٬۰۰۰')
            ->set('form.items', ['product:'.$this->product->id, 'pro:1'])
            ->call('save')
            ->assertNotified('بسته ساخته شد (پیش‌نویس)')
            ->call('publish', $existing->id)
            ->assertNotified('بسته منتشر شد');

        $this->assertDatabaseHas('bundles', ['slug' => 'second-kit', 'price_toman' => 300_000, 'status' => 'draft']);
        $this->assertSame(BundleStatus::Published, $existing->refresh()->status);
    }

    public function test_a_price_not_below_the_sum_is_refused(): void
    {
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Super);
        $this->bundle(publish: false);

        Livewire::actingAs($admin->fresh())->test(BundlesPage::class)
            ->set('form.title', 'بسته گران')
            ->set('form.slug', 'pricey')
            ->set('form.description', 'قیمتش از جمع اجزا بیشتر است.')
            ->set('form.price', '400000')
            ->set('form.items', ['product:'.$this->product->id, 'pro:1'])
            ->call('save')
            ->assertNotified('ذخیره نشد');

        $this->assertSame(1, Bundle::query()->count());
    }

    public function test_non_admins_cannot_open_the_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertFalse(BundlesPage::canAccess());
    }
}
