<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Workspace\Actions\ReportIncident;
use App\Modules\Workspace\Actions\ResolveIncident;
use App\Modules\Workspace\Domain\Enums\Service;
use App\Modules\Workspace\Domain\Enums\ServiceState;
use App\Modules\Workspace\Domain\ServiceIncident;
use App\Modules\Workspace\Domain\UserNotification;
use App\Modules\Workspace\Services\StatusBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * صفحه وضعیت سرویس (DEC-23): همه‌چیز از جدول رویدادها.
 */
final class StatusPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_with_no_incidents_every_service_is_operational(): void
    {
        $this->get(route('workspace.status'))
            ->assertOk()
            ->assertSee('همه سرویس‌ها برقرارند')
            ->assertSee('ورود با کد یک‌بارمصرف')
            ->assertSee('noindex', escape: false);
    }

    public function test_an_open_incident_changes_the_service_and_the_overall_state(): void
    {
        $this->report(Service::Payments, ServiceState::Outage, 'درگاه پرداخت پاسخ نمی‌دهد');

        $board = $this->app->make(StatusBoard::class);

        $this->assertSame(ServiceState::Outage, $board->current()['payments']);
        $this->assertSame(ServiceState::Operational, $board->current()['site']);
        $this->assertSame(ServiceState::Outage, $board->overall());

        $this->get(route('workspace.status'))
            ->assertSee('بخشی از سرویس‌ها اختلال دارند')
            ->assertSee('درگاه پرداخت پاسخ نمی‌دهد');
    }

    public function test_the_worst_of_several_open_incidents_wins(): void
    {
        $this->report(Service::Site, ServiceState::Degraded, 'کندی');
        $this->report(Service::Site, ServiceState::Outage, 'قطعی');

        $this->assertSame(ServiceState::Outage, $this->app->make(StatusBoard::class)->current()['site']);
    }

    public function test_scheduled_maintenance_does_not_change_the_state_before_it_starts(): void
    {
        $this->report(Service::Downloads, ServiceState::Maintenance, 'ارتقای انبار فایل', Carbon::now()->addDay());

        $this->assertSame(ServiceState::Operational, $this->app->make(StatusBoard::class)->current()['downloads']);

        $this->get(route('workspace.status'))
            ->assertSee('نگهداری پیش‌رو')
            ->assertSee('ارتقای انبار فایل');
    }

    public function test_the_daily_bar_covers_the_configured_days_and_marks_past_incidents(): void
    {
        Carbon::setTestNow('2026-09-18 10:00:00');
        $incident = $this->report(Service::Otp, ServiceState::Degraded, 'تأخیر پیامک');

        Carbon::setTestNow('2026-09-18 14:00:00');
        $this->app->make(ResolveIncident::class)->handle($incident, 'اپراتور پیامک برگشت.', null);

        Carbon::setTestNow('2026-09-20 12:00:00');

        $bars = $this->app->make(StatusBoard::class)->history();

        $this->assertCount(45, $bars['otp']);
        $this->assertSame(ServiceState::Degraded, $bars['otp'][42]['state']);
        $this->assertSame(ServiceState::Operational, $bars['otp'][44]['state']);

        Carbon::setTestNow();
    }

    public function test_subscribers_are_notified_when_the_incident_is_resolved(): void
    {
        $incident = $this->report(Service::Otp, ServiceState::Degraded, 'تأخیر پیامک');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('workspace.status.subscribe', $incident->uuid))
            ->assertRedirect(route('workspace.status'));

        // تکرار بی‌اثر است.
        $this->post(route('workspace.status.subscribe', $incident->uuid));

        $this->get(route('workspace.status'))->assertSee('خبرتان می‌کنیم');

        $this->app->make(ResolveIncident::class)->handle($incident, 'برطرف شد.', null);

        $notification = UserNotification::query()->where('user_id', $user->id)->sole();
        $this->assertSame('workspace.incident_resolved', $notification->kind);
        $this->assertSame('برطرف شد.', $notification->body);
    }

    public function test_a_guest_cannot_subscribe(): void
    {
        $incident = $this->report(Service::Otp, ServiceState::Degraded, 'تأخیر پیامک');

        $this->post(route('workspace.status.subscribe', $incident->uuid))->assertRedirect(route('login'));
    }

    public function test_an_incident_cannot_be_operational_or_resolved_twice(): void
    {
        try {
            $this->report(Service::Site, ServiceState::Operational, 'هیچ');
            $this->fail('وضعیت «برقرار» نباید ثبت شود.');
        } catch (InvalidArgumentException) {
        }

        $incident = $this->report(Service::Site, ServiceState::Degraded, 'کندی');
        $this->app->make(ResolveIncident::class)->handle($incident, null, null);

        $this->expectException(InvalidArgumentException::class);
        $this->app->make(ResolveIncident::class)->handle($incident->fresh() ?? $incident, null, null);
    }

    public function test_only_content_and_super_admins_manage_the_status_page(): void
    {
        $page = '/'.config('admin.path').'/service-status';

        $this->actingAs($this->adminWith(AdminRole::Content))->get($page)->assertOk()->assertSee('ثبت رویداد تازه');
        $this->actingAs($this->adminWith(AdminRole::Super))->get($page)->assertOk();
        $this->actingAs($this->adminWith(AdminRole::Finance))->get($page)->assertForbidden();
    }

    private function report(Service $service, ServiceState $state, string $title, ?Carbon $startedAt = null): ServiceIncident
    {
        return $this->app->make(ReportIncident::class)->handle($service, $state, $title, null, $startedAt, null);
    }

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
