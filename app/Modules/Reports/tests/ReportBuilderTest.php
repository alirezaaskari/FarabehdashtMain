<?php

declare(strict_types=1);

namespace App\Modules\Reports\Tests;

use App\Models\User;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * گزارش‌ساز چهارمرحله‌ای، از چشم کاربر.
 */
final class ReportBuilderTest extends TestCase
{
    use RefreshDatabase;
    use ReportFixtures;

    public function test_the_builder_needs_an_account(): void
    {
        $this->get(route('reports.index'))->assertRedirect();
        $this->get(route('reports.create'))->assertRedirect();
    }

    public function test_step_one_lists_the_users_projects_and_calculations_only(): void
    {
        $user = User::factory()->create();
        $this->project($user);
        $this->calculation($user, 'محاسبه من');
        $this->calculation(User::factory()->create(), 'محاسبه دیگری');

        $this->actingAs($user)->get(route('reports.create', ['source' => 'project']))
            ->assertOk()
            ->assertSee('پایش صدای سالن پرس')
            ->assertSee('محاسبه‌های ذخیره‌شده');

        $this->actingAs($user)->get(route('reports.create', ['source' => 'calculations']))
            ->assertOk()
            ->assertSee('محاسبه من')
            ->assertDontSee('محاسبه دیگری');
    }

    public function test_choosing_a_project_creates_a_draft_prefilled_from_it(): void
    {
        $user = User::factory()->create(['name' => 'مریم کارشناس']);
        $project = $this->project($user);

        $response = $this->actingAs($user)->post(route('reports.store'), [
            'source' => 'project',
            'references' => [$project->uuid],
        ]);

        $report = Report::query()->sole();

        $response->assertRedirect(route('reports.details', $report->uuid));
        $this->assertSame(ReportStatus::Draft, $report->status);
        $this->assertSame('گزارش پایش صدای سالن پرس', $report->title);
        $this->assertSame('شرکت نمونه', $report->client_name);
        $this->assertSame('مریم کارشناس', $report->author_name);
        $this->assertNull($report->tracking_code);
    }

    public function test_another_users_project_cannot_be_the_source(): void
    {
        $project = $this->project(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->from(route('reports.create'))
            ->post(route('reports.store'), ['source' => 'project', 'references' => [$project->uuid]])
            ->assertRedirect(route('reports.create'))
            ->assertSessionHasErrors('references');

        $this->assertSame(0, Report::query()->count());
    }

    public function test_several_calculations_go_into_one_report(): void
    {
        $user = User::factory()->create();
        $first = $this->calculation($user, 'اتاق کنترل');
        $second = $this->calculation($user, 'سالن ذوب');

        $this->actingAs($user)->post(route('reports.store'), [
            'source' => 'calculations',
            'references' => [$first->uuid, $second->uuid],
        ])->assertRedirect();

        $this->assertSame([$first->uuid, $second->uuid], Report::query()->sole()->source_references);
    }

    public function test_steps_two_and_three_save_the_draft(): void
    {
        $user = User::factory()->create();
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->get(route('reports.details', $report->uuid))->assertOk()->assertSee('مشخصات گزارش');

        $this->actingAs($user)->put(route('reports.details.save', $report->uuid), [
            'title' => 'گزارش پایش صدا — مهر',
            'client_name' => 'شرکت نمونه',
            'site' => 'سالن پرس',
            'measured_on' => 'مهر ۱۴۰۵',
            'author_name' => 'مریم کارشناس',
            'include_equipment' => '1',
        ])->assertRedirect(route('reports.findings', $report->uuid));

        $this->actingAs($user)->put(route('reports.findings.save', $report->uuid), [
            'findings' => 'تراز در ایستگاه پرس بالاتر از حد مجاز است.',
            'recommendations' => 'محصورسازی پرس.',
        ])->assertRedirect(route('reports.review', $report->uuid));

        $report->refresh();
        $this->assertSame('سالن پرس', $report->site);
        $this->assertTrue($report->include_equipment);
        $this->assertFalse($report->include_method);
        $this->assertSame('محصورسازی پرس.', $report->recommendations);

        $this->actingAs($user)->get(route('reports.review', $report->uuid))->assertOk()->assertSee('پیش‌نمایش');
    }

    public function test_a_free_user_sees_the_whole_builder_and_a_pdf_preview(): void
    {
        $user = User::factory()->create();
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->get(route('reports.review', $report->uuid))
            ->assertOk()
            ->assertSee('پیش‌نویس و پیش‌نمایش برای همه آزاد است')
            ->assertDontSee('صدور گزارش</button>', escape: false);

        $response = $this->actingAs($user)->get(route('reports.preview', $report->uuid));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_another_user_gets_not_found_everywhere(): void
    {
        $report = $this->draftFromProject(User::factory()->create());
        $stranger = User::factory()->create();

        foreach (['reports.show', 'reports.details', 'reports.review', 'reports.preview', 'reports.download'] as $route) {
            $this->actingAs($stranger)->get(route($route, $report->uuid))->assertNotFound();
        }

        $this->actingAs($stranger)->delete(route('reports.destroy', $report->uuid))->assertNotFound();
    }

    public function test_a_draft_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->delete(route('reports.destroy', $report->uuid))->assertRedirect(route('reports.index'));

        $this->assertSame(0, Report::query()->count());
    }

    public function test_the_workspace_sidebar_and_dashboard_link_to_reports(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('هنوز گزارشی نساخته‌اید');

        $this->actingAs($user)->get(route('workspace.dashboard'))
            ->assertOk()
            ->assertSee(route('reports.index'), escape: false);
    }
}
