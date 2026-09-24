<?php

declare(strict_types=1);

namespace App\Modules\Reports\Tests;

use App\Models\User;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Projects\Domain\Equipment;
use App\Modules\Projects\Domain\ProjectReading;
use App\Modules\Reports\Actions\IssueReport;
use App\Modules\Reports\Actions\ReviseReport;
use App\Modules\Reports\Actions\RevokeReport;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Domain\TrackingCode;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Entitlement\EntitlementDenied;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

/**
 * صدور: منجمدکردن، PDF با هش، شناسه رهگیری، نسخه اصلاحی و ابطال.
 */
final class IssueReportTest extends TestCase
{
    use RefreshDatabase;
    use ReportFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_issuing_needs_pro_and_leaves_the_draft_untouched(): void
    {
        $user = User::factory()->create();
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->post(route('reports.issue', $report->uuid))->assertRedirect();

        $this->assertSame(ReportStatus::Draft, $report->fresh()?->status);

        $this->expectException(EntitlementDenied::class);
        $this->issue($report, $user);
    }

    public function test_turning_off_the_subscription_opens_issuing_to_everyone(): void
    {
        $user = User::factory()->create();
        $report = $this->draftFromProject($user);

        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::ProSubscription, false);

        $this->assertSame(ReportStatus::Issued, $this->issue($report, $user)->status);
    }

    public function test_issuing_freezes_the_data_stores_the_pdf_and_records_its_hash(): void
    {
        $this->allowIssuing();
        $user = User::factory()->create();
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->post(route('reports.issue', $report->uuid))
            ->assertRedirect(route('reports.show', $report->uuid));

        $report->refresh();

        $this->assertSame(ReportStatus::Issued, $report->status);
        $this->assertMatchesRegularExpression('/^FBH-[0-9A-HJKMNP-TV-Z]{4}-[0-9A-HJKMNP-TV-Z]{4}$/', (string) $report->tracking_code);
        $this->assertNotNull($report->issued_at);

        $bytes = Storage::disk('local')->get((string) $report->pdf_path);
        $this->assertStringStartsWith('%PDF', (string) $bytes);
        $this->assertSame(hash('sha256', (string) $bytes), $report->pdf_sha256);

        $document = $report->document();
        $this->assertNotNull($document);
        $this->assertCount(2, $document->data->measurements);
        $this->assertSame('91.4', $document->data->measurements[0]->value);
        $this->assertSame('SN-4471', $document->data->equipment[0]->serialNumber);
        $this->assertSame($report->tracking_code, $document->trackingCode);

        $response = $this->actingAs($user)->get(route('reports.download', $report->uuid));
        $response->assertOk();
        $this->assertSame($bytes, $response->streamedContent());
    }

    public function test_a_later_change_to_the_project_does_not_reach_the_issued_report(): void
    {
        $this->allowIssuing();
        $user = User::factory()->create();
        $report = $this->issue($this->draftFromProject($user), $user);
        $snapshot = $report->snapshot;

        ProjectReading::query()->update(['value' => 60.0]);
        Equipment::query()->update(['serial_number' => 'CHANGED', 'calibration_valid_until' => now()->subYear()]);

        $report->refresh();

        $this->assertSame($snapshot, $report->snapshot);
        $this->assertSame('91.4', $report->document()?->data->measurements[0]->value);
        $this->assertSame('SN-4471', $report->document()?->data->equipment[0]->serialNumber);
    }

    public function test_an_uncalibrated_instrument_needs_explicit_acknowledgement_which_is_recorded(): void
    {
        $this->allowIssuing();
        $user = User::factory()->create();
        $report = $this->draftFromProject($user, $this->project($user, '-3 days'));

        try {
            $this->issue($report, $user);
            $this->fail('بدون تأیید نباید صادر شود.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('کالیبراسیون', $exception->getMessage());
        }

        $issued = $this->issue($report->fresh() ?? $report, $user, acknowledge: true);

        $this->assertTrue($issued->document()?->calibrationAcknowledged);
        $this->assertTrue($issued->document()?->data->equipment[0]->blocking);

        $audit = DB::table('audit_logs')->where('action', 'reports.issued')->sole();
        $this->assertStringContainsString('calibration_acknowledged', (string) $audit->context);
        $this->assertStringNotContainsString('شرکت نمونه', (string) $audit->after.(string) $audit->context);
    }

    public function test_the_owner_is_notified_in_the_workspace(): void
    {
        $this->allowIssuing();
        $user = User::factory()->create();
        $report = $this->issue($this->draftFromProject($user), $user);

        $notice = UserNotification::query()->where('user_id', $user->id)->sole();

        $this->assertSame('reports.issued', $notice->kind);
        $this->assertStringContainsString((string) $report->tracking_code, $notice->title);
    }

    public function test_an_issued_report_is_never_edited_only_revised(): void
    {
        $this->allowIssuing();
        $user = User::factory()->create();
        $first = $this->issue($this->draftFromProject($user), $user);

        $this->actingAs($user)->put(route('reports.details.save', $first->uuid), [
            'title' => 'عوض شد', 'author_name' => 'کسی',
        ])->assertRedirect(route('reports.show', $first->uuid));
        $this->assertNotSame('عوض شد', $first->fresh()?->title);

        $this->actingAs($user)->post(route('reports.revise', $first->uuid))->assertRedirect();
        $draft = Report::query()->where('supersedes_id', $first->id)->sole();

        $this->assertSame(2, $draft->revision);
        $this->assertSame(ReportStatus::Issued, $first->fresh()?->status, 'پیش‌نویس اصلاحی هنوز چیزی را باطل نمی‌کند.');
        $this->assertSame($draft->id, $this->app->make(ReviseReport::class)->handle($first->fresh() ?? $first)->id);

        $second = $this->issue($draft, $user);
        $first->refresh();

        $this->assertSame(ReportStatus::Superseded, $first->status);
        $this->assertSame($second->id, $first->superseded_by_id);
        $this->assertSame($first->tracking_code, $second->document()?->supersedesCode);
        $this->assertNotSame($first->tracking_code, $second->tracking_code);
    }

    public function test_the_owner_can_revoke_and_a_revoked_report_cannot_be_revised(): void
    {
        $this->allowIssuing();
        $user = User::factory()->create();
        $report = $this->issue($this->draftFromProject($user), $user);

        $this->actingAs($user)->post(route('reports.revoke', $report->uuid), ['reason' => 'اشتباه در نام ایستگاه'])
            ->assertRedirect(route('reports.show', $report->uuid));

        $report->refresh();
        $this->assertSame(ReportStatus::Revoked, $report->status);
        $this->assertTrue(Storage::disk('local')->exists((string) $report->pdf_path), 'فایل باطل‌شده حذف نمی‌شود.');
        $this->assertSame(0, UserNotification::query()->where('kind', 'reports.revoked')->count());

        $this->expectException(LogicException::class);
        $this->app->make(ReviseReport::class)->handle($report);
    }

    public function test_a_draft_cannot_be_revoked(): void
    {
        $user = User::factory()->create();

        $this->expectException(LogicException::class);
        $this->app->make(RevokeReport::class)->handle($this->draftFromProject($user), 'دلیل', $user->id);
    }

    public function test_tracking_codes_are_parsed_leniently_and_never_sequential(): void
    {
        $this->assertSame('FBH-7K3M-Q9TD', TrackingCode::parse('fbh 7k3m q9td')?->value);
        $this->assertSame('FBH-7K3M-Q9TD', TrackingCode::parse('7K3MQ9TD')?->value);
        $this->assertSame('FBH-0K1M-Q9TD', TrackingCode::parse('FBH-OKIM-Q9TD')?->value);
        $this->assertSame('FBH-1234-5678', TrackingCode::parse('FBH-۱۲۳۴-۵۶۷۸')?->value);
        $this->assertNull(TrackingCode::parse('FBH-1234'));
        $this->assertNull(TrackingCode::parse('FBH-UUUU-UUUU'));

        $codes = array_map(static fn (): string => TrackingCode::generate()->value, range(1, 200));
        $this->assertCount(200, array_unique($codes));
    }

    private function issue(Report $report, User $user, bool $acknowledge = false): Report
    {
        return $this->app->make(IssueReport::class)->handle($report, $user, $acknowledge);
    }
}
