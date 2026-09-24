<?php

declare(strict_types=1);

namespace App\Modules\Reports\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Reports\Actions\IssueReport;
use App\Modules\Reports\Actions\ReviseReport;
use App\Modules\Reports\Actions\RevokeReport;
use App\Modules\Reports\Actions\SaveReportDraft;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Filament\Pages\IssuedReportsPage;
use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * صفحه عمومی تأیید اصالت (DEC-28) و ابطال از پنل مدیر.
 */
final class VerificationTest extends TestCase
{
    use RefreshDatabase;
    use ReportFixtures;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->allowIssuing();
        $this->owner = User::factory()->create(['name' => 'مریم کارشناس']);
    }

    public function test_an_issued_report_verifies_with_metadata_but_never_its_content(): void
    {
        $report = $this->issued();

        $this->get(route('reports.verify.show', $report->tracking_code))
            ->assertOk()
            ->assertSee('noindex', escape: false)
            ->assertSee('این گزارش معتبر است و آخرین نسخه آن است')
            ->assertSee('گزارش پایش صدای سالن پرس')
            ->assertSee('مریم کارشناس')
            ->assertSee((string) $report->pdf_sha256)
            ->assertDontSee('شرکت نمونه')
            ->assertDontSee('یافته محرمانه')
            ->assertDontSee('91.4');
    }

    public function test_an_unknown_code_says_it_was_not_issued(): void
    {
        $this->get(route('reports.verify.show', 'FBH-0000-0000'))
            ->assertOk()
            ->assertSee('گزارشی با این شناسه صادر نشده است');
    }

    public function test_the_form_normalises_what_people_type(): void
    {
        $report = $this->issued();
        $typed = strtolower(str_replace('-', ' ', (string) $report->tracking_code));

        $this->get(route('reports.verify.form', ['code' => $typed]))
            ->assertRedirect(route('reports.verify.show', $report->tracking_code));

        $this->get(route('reports.verify.form', ['code' => 'چیز نامربوط']))
            ->assertOk()
            ->assertSee('این شناسه شکل درستی ندارد');

        $this->get(route('reports.verify.form'))->assertOk()->assertSee('شناسه رهگیری');
    }

    public function test_a_superseded_report_points_to_the_current_version(): void
    {
        $first = $this->issued();
        $draft = $this->app->make(ReviseReport::class)->handle($first);
        $second = $this->app->make(IssueReport::class)->handle($draft, $this->owner);

        $this->get(route('reports.verify.show', $first->tracking_code))
            ->assertSee('نسخه تازه‌تری از این گزارش صادر شده است')
            ->assertSee((string) $second->tracking_code);
    }

    public function test_a_revoked_report_says_so(): void
    {
        $report = $this->app->make(RevokeReport::class)->handle($this->issued(), 'اشتباه', $this->owner->id);

        $this->get(route('reports.verify.show', $report->tracking_code))
            ->assertSee('این گزارش باطل شده است');
    }

    public function test_guessing_codes_is_rate_limited(): void
    {
        config(['reports.verify_per_minute' => 3]);

        foreach (range(1, 3) as $attempt) {
            $this->get(route('reports.verify.show', 'FBH-0000-000'.$attempt))->assertOk();
        }

        $this->get(route('reports.verify.show', 'FBH-0000-0004'))->assertTooManyRequests();
    }

    public function test_content_admins_can_revoke_and_the_owner_is_told(): void
    {
        $report = $this->issued();
        $page = '/'.config('admin.path').'/issued-reports';

        $this->actingAs($this->adminWith(AdminRole::Finance))->get($page)->assertForbidden();

        $admin = $this->adminWith(AdminRole::Content);
        $this->actingAs($admin)->get($page)->assertOk()->assertSee((string) $report->tracking_code);

        Livewire::actingAs($admin)
            ->test(IssuedReportsPage::class)
            ->set('reasons.'.$report->id, 'سوءاستفاده از نام شرکت')
            ->call('revoke', $report->id)
            ->assertSet('error', null);

        $this->assertSame(ReportStatus::Revoked, $report->fresh()?->status);

        $notice = UserNotification::query()->where('user_id', $this->owner->id)->where('kind', 'reports.revoked')->sole();
        $this->assertSame('سوءاستفاده از نام شرکت', $notice->body);
    }

    private function issued(): Report
    {
        $draft = $this->draftFromProject($this->owner);
        $this->app->make(SaveReportDraft::class)->handle($draft, ['findings' => 'یافته محرمانه']);

        return $this->app->make(IssueReport::class)->handle($draft, $this->owner);
    }

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
