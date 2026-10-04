<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Chemicals\Domain\Enums\ErrorReportStatus;
use App\Modules\Chemicals\Domain\Enums\ErrorReportTopic;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Domain\SubstanceErrorReport;
use App\Modules\Chemicals\Filament\Pages\ErrorReportsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** «گزارش اشتباه» در صفحه ماده و بررسی آن در پنل. */
final class ErrorReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_asked_to_log_in_and_cannot_post(): void
    {
        $substance = $this->substance();

        $this->get(route('chemicals.show', $substance->slug))
            ->assertOk()
            ->assertSee('اشتباهی در این صفحه دیده‌اید؟')
            ->assertSee('برای گزارش وارد شوید')
            ->assertDontSee('فرستادن گزارش');

        $this->post(route('chemicals.report', $substance->slug), $this->payload())->assertRedirect(route('login'));
        $this->assertSame(0, SubstanceErrorReport::query()->count());
    }

    public function test_a_user_reports_an_error_with_a_source(): void
    {
        $substance = $this->substance();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('chemicals.show', $substance->slug))->assertSee('فرستادن گزارش');

        $this->actingAs($user)
            ->post(route('chemicals.report', $substance->slug), $this->payload())
            ->assertRedirect(route('chemicals.show', $substance->slug).'#report');

        $report = SubstanceErrorReport::query()->sole();
        $this->assertSame($substance->id, $report->substance_id);
        $this->assertSame($user->id, $report->user_id);
        $this->assertSame(ErrorReportTopic::LimitValue, $report->topic);
        $this->assertSame(ErrorReportStatus::Open, $report->status);
        $this->assertSame('https://www.cdc.gov/niosh/npg/npgd0619.html', $report->source_url);

        $this->actingAs($user)->get(route('chemicals.show', $substance->slug))->assertSee('گزارش ثبت شد');
        $this->assertDatabaseHas('audit_logs', ['action' => 'chemicals.error_reported']);
    }

    public function test_the_report_is_validated(): void
    {
        $substance = $this->substance();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('chemicals.report', $substance->slug), $this->payload(['message' => 'کوتاه', 'source_url' => 'http://example.com', 'topic' => 'nope']))
            ->assertSessionHasErrorsIn('report', ['message', 'source_url', 'topic']);
        $this->assertSame(0, SubstanceErrorReport::query()->count());

        $draft = $this->substance(SubstanceStatus::Draft, '71-43-2');
        $this->actingAs($user)->post(route('chemicals.report', $draft->slug), $this->payload())->assertNotFound();
    }

    public function test_the_daily_limit_comes_from_the_admin_setting(): void
    {
        config(['chemicals.reports.per_day' => 2]);
        $substance = $this->substance();
        $user = User::factory()->create();

        foreach ([1, 2] as $_) {
            $this->actingAs($user)->post(route('chemicals.report', $substance->slug), $this->payload())->assertSessionHasNoErrors();
        }

        $this->actingAs($user)
            ->post(route('chemicals.report', $substance->slug), $this->payload())
            ->assertSessionHasErrorsIn('report', ['message']);
        $this->assertSame(2, SubstanceErrorReport::query()->count());

        // سقف شخصی است: کاربر دیگر هنوز می‌تواند گزارش بدهد.
        $this->actingAs(User::factory()->create())->post(route('chemicals.report', $substance->slug), $this->payload())->assertSessionHasNoErrors();
    }

    public function test_a_content_admin_closes_a_report_without_touching_the_substance(): void
    {
        $substance = $this->substance();
        $this->actingAs(User::factory()->create())->post(route('chemicals.report', $substance->slug), $this->payload());
        $report = SubstanceErrorReport::query()->sole();

        $admin = $this->admin(AdminRole::Content);
        $this->actingAs($admin)->get('/'.config('admin.path').'/chemical-error-reports')
            ->assertOk()
            ->assertSee('حد STEL در صفحه NIOSH');

        Livewire::test(ErrorReportsPage::class)
            ->set('notes.'.$report->id, 'عدد از ویرایشگر اصلاح شد')
            ->call('markFixed', $report->id)
            ->assertSet('open', []);

        $report->refresh();
        $this->assertSame(ErrorReportStatus::Fixed, $report->status);
        $this->assertSame($admin->id, $report->resolved_by);
        $this->assertSame('عدد از ویرایشگر اصلاح شد', $report->admin_note);
        $this->assertSame('ماده آزمایشی', $substance->fresh()?->name_fa);
        $this->assertDatabaseHas('audit_logs', ['action' => 'chemicals.error_report_resolved']);
    }

    public function test_a_finance_admin_cannot_open_the_reports_page(): void
    {
        $this->actingAs($this->admin(AdminRole::Finance))
            ->get('/'.config('admin.path').'/chemical-error-reports')
            ->assertForbidden();
    }

    /** @param  array<string, string>  $overrides
     *  @return array<string, string> */
    private function payload(array $overrides = []): array
    {
        return [
            'topic' => ErrorReportTopic::LimitValue->value,
            'message' => 'حد STEL در صفحه NIOSH این ماده ۱۵۰ ppm است، نه ۱۲۵.',
            'source_url' => 'https://www.cdc.gov/niosh/npg/npgd0619.html',
            ...$overrides,
        ];
    }

    private function admin(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    private function substance(SubstanceStatus $status = SubstanceStatus::Published, string $cas = '108-88-3'): Substance
    {
        return Substance::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => 'test-'.Str::lower(Str::random(8)),
            'cas_number' => $cas,
            'name_fa' => 'ماده آزمایشی',
            'name_en' => 'Test Substance',
            'status' => $status,
        ])->refresh();
    }
}
