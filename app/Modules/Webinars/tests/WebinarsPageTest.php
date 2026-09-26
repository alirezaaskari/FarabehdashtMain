<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Webinars\Domain\Enums\WebinarStatus;
use App\Modules\Webinars\Domain\Webinar;
use App\Modules\Webinars\Filament\Pages\WebinarsPage;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class WebinarsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_creates_and_publishes_a_webinar(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Super);

        Livewire::actingAs($admin->fresh())->test(WebinarsPage::class)
            ->set('form.title', 'وبینار تنش گرمایی')
            ->set('form.slug', 'heat-webinar')
            ->set('form.description', 'محاسبه شاخص WBGT و برنامه کار و استراحت.')
            ->set('form.instructor_name', 'مهندس نمونه')
            ->set('form.starts_at', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('form.price', '۰')
            ->set('form.join_url', 'https://www.skyroom.online/ch/fbh/heat')
            ->call('save')
            ->assertNotified('رویداد ساخته شد (پیش‌نویس)')
            ->call('publish', 1)
            ->assertNotified('رویداد منتشر شد');

        $this->assertSame(WebinarStatus::Published, Webinar::query()->sole()->status);
    }

    public function test_non_admins_cannot_open_the_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertFalse(WebinarsPage::canAccess());
    }
}
