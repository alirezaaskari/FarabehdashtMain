<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Tests;

use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Domain\ProjectFile;
use App\Modules\Marketplace\Filament\Pages\MarketReviewPage;
use App\Modules\Marketplace\Seo\MarketSitemapSource;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Seo\SitemapUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * تعریف پروژه و فهرست عمومی (بخش ۲۱-۲): تأیید مدیر (DEC-76)، نوع کار از
 * فهرست خدمت‌ها (DEC-87)، کمینه بودجه و مهلت پیشنهاد (DEC-79)، نام پنهان
 * کارفرما (DEC-84) و پروژه خصوصی (DEC-90).
 */
final class ProjectPostingTest extends TestCase
{
    use MarketFixtures;
    use RefreshDatabase;

    public function test_a_project_waits_for_the_admin_and_is_published_on_approval(): void
    {
        $client = $this->client();

        $this->actingAs($client)->get(route('market.client.create'))->assertOk()->assertSee('data-page-help="client-project-form"', false);
        $this->actingAs($client)->post(route('market.client.store'), $this->projectForm())->assertRedirect(route('market.client.index'));

        $project = MarketProject::query()->sole();
        $this->assertSame(ProjectStatus::Pending, $project->status);
        $this->assertSame(8_000_000, $project->budget_min_toman);
        auth()->logout();
        $this->get(route('market.show', $project->id))->assertNotFound();

        $this->actingAs($this->admin(AdminRole::Content));
        Livewire::test(MarketReviewPage::class)->assertSee('اندازه‌گیری صدای سالن نورد')->call('approve', $project->id)->assertSet('rows', []);

        $project->refresh();
        $this->assertSame(ProjectStatus::Open, $project->status);
        $this->assertTrue($project->bids_close_at?->isSameDay(Carbon::now()->addDays(14)));
        $this->assertSame(1, UserNotification::query()->where('user_id', $client->id)->where('kind', 'marketplace.project_approved')->count());

        auth()->logout();
        $this->get(route('market.show', $project->id))->assertOk()
            ->assertSee('اندازه‌گیری صدای سالن نورد')
            ->assertSee('کارفرما در اصفهان')
            ->assertDontSee('noindex', false);
        $this->get(route('market.index'))->assertOk()->assertSee('اندازه‌گیری صدای سالن نورد')->assertSee('data-page-help="market"', false);
    }

    public function test_a_rejected_project_comes_back_with_the_note_and_can_be_fixed(): void
    {
        $client = $this->client();
        $this->actingAs($client)->post(route('market.client.store'), $this->projectForm());
        $project = MarketProject::query()->sole();

        $this->actingAs($this->admin(AdminRole::Content));
        Livewire::test(MarketReviewPage::class)->call('reject', $project->id)->assertNotified();
        $this->assertSame(ProjectStatus::Pending, $project->refresh()->status);

        Livewire::test(MarketReviewPage::class)->set('notes.'.$project->id, 'شماره تماس را از متن بردارید')->call('reject', $project->id);
        $this->assertSame(ProjectStatus::Rejected, $project->refresh()->status);

        $this->actingAs($client)->get(route('market.client.index'))->assertOk()->assertSee('شماره تماس را از متن بردارید');
        $this->actingAs($client)->put(route('market.client.update', $project->uuid), $this->projectForm(['title' => 'اندازه‌گیری صدای دو سالن نورد']))
            ->assertRedirect(route('market.client.index'));

        $project->refresh();
        $this->assertSame(ProjectStatus::Pending, $project->status);
        $this->assertSame('اندازه‌گیری صدای دو سالن نورد', $project->title);
    }

    public function test_an_open_project_is_not_edited_only_closed(): void
    {
        $client = $this->client();
        $project = $this->publishedProject($client);

        $this->actingAs($client)->get(route('market.client.edit', $project->uuid))->assertRedirect(route('market.client.index'));
        $this->actingAs($client)->put(route('market.client.update', $project->uuid), $this->projectForm(['title' => 'عنوان تازه برای پروژه باز']))
            ->assertSessionHasErrors('project');

        $this->actingAs($client)->post(route('market.client.close', $project->uuid))->assertRedirect(route('market.client.index'));
        $this->assertSame(ProjectStatus::Closed, $project->refresh()->status);

        auth()->logout();
        $this->get(route('market.show', $project->id))->assertOk()->assertSee('noindex', false)->assertSee('دیگر پیشنهاد نمی‌پذیرد');
        $this->get(route('market.index'))->assertDontSee($project->title);
    }

    public function test_the_rules_hold_budget_service_and_a_verified_mobile(): void
    {
        $this->actingAs($this->client())->post(route('market.client.store'), $this->projectForm(['budget_min' => '500000', 'budget_max' => '900000']))
            ->assertSessionHasErrors(['budget_min', 'budget_max']);
        $this->actingAs($this->client())->post(route('market.client.store'), $this->projectForm(['budget_min' => '9000000', 'budget_max' => '8000000']))
            ->assertSessionHasErrors('budget_max');
        $this->actingAs($this->client())->post(route('market.client.store'), $this->projectForm(['service' => 'plumbing']))
            ->assertSessionHasErrors('service');

        $unverified = User::factory()->create(['mobile_verified_at' => null]);
        $this->actingAs($unverified)->post(route('market.client.store'), $this->projectForm())->assertSessionHasErrors('project');

        // کمینه بودجه تنظیم پنل است.
        config(['marketplace.projects.budget_min_toman' => 10_000_000]);
        $this->actingAs($this->client())->post(route('market.client.store'), $this->projectForm())->assertSessionHasErrors('budget_min');

        $this->assertSame(0, MarketProject::query()->count());
    }

    public function test_a_remote_project_needs_no_city(): void
    {
        $this->actingAs($this->client())->post(route('market.client.store'), $this->projectForm([
            'service' => 'hse-plan', 'remote' => '1', 'province' => '', 'city' => '',
        ]))->assertSessionHasNoErrors();

        $project = MarketProject::query()->sole();
        $this->assertTrue($project->remote);
        $this->assertNull($project->city);
    }

    public function test_the_client_name_shows_only_when_the_client_chooses(): void
    {
        $project = $this->publishedProject(null, ['client_name' => 'فولاد سپاهان']);
        $this->get(route('market.show', $project->id))->assertSee('کارفرما در اصفهان')->assertDontSee('فولاد سپاهان');

        $named = $this->publishedProject(null, ['client_name' => 'فولاد سپاهان', 'show_client_name' => '1', 'title' => 'ارزیابی روشنایی انبار مرکزی']);
        $this->get(route('market.show', $named->id))->assertSee('فولاد سپاهان');
    }

    public function test_a_private_project_is_hidden_from_the_list_the_sitemap_and_strangers(): void
    {
        $client = $this->client();
        $project = $this->publishedProject($client, ['private' => '1']);
        $public = $this->publishedProject(null, ['title' => 'ارزیابی ریسک خط بسته‌بندی', 'service' => 'risk']);

        $this->get(route('market.index'))->assertDontSee($project->title)->assertSee($public->title);
        $this->get(route('market.show', $project->id))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('market.show', $project->id))->assertNotFound();
        $this->actingAs($client)->get(route('market.show', $project->id))->assertOk()->assertSee('پروژه خصوصی')->assertSee('noindex', false);

        $urls = array_map(static fn (SitemapUrl $url): string => $url->loc, iterator_to_array($this->app->make(MarketSitemapSource::class)->sitemapUrls(), false));
        $this->assertContains(route('market.show', $public->id), $urls);
        $this->assertNotContains(route('market.show', $project->id), $urls);
    }

    public function test_service_and_city_pages_are_indexed_only_with_two_open_projects(): void
    {
        $this->publishedProject();
        $this->get(route('market.index', ['service' => 'noise']))->assertRedirect(route('market.service', 'noise'));
        $this->get(route('market.service', 'noise'))->assertOk()->assertSee('noindex', false);
        $this->get(route('market.service', 'plumbing'))->assertNotFound();

        $this->publishedProject(null, ['title' => 'اندازه‌گیری صدای کمپرسورخانه']);
        $this->get(route('market.service', 'noise'))->assertOk()->assertDontSee('noindex', false);
        $this->get(route('market.city', 'isfahan'))->assertOk()->assertDontSee('noindex', false);
        $this->get(route('market.index', ['service' => 'noise', 'city' => 'isfahan']))->assertOk()->assertSee('noindex', false);

        $urls = array_map(static fn (SitemapUrl $url): string => $url->loc, iterator_to_array($this->app->make(MarketSitemapSource::class)->sitemapUrls(), false));
        $this->assertContains(route('market.service', 'noise'), $urls);
        $this->assertContains(route('market.city', 'isfahan'), $urls);
    }

    public function test_an_expired_bid_window_leaves_the_list(): void
    {
        $project = $this->publishedProject();

        $this->travel(15)->days();

        $this->get(route('market.index'))->assertDontSee($project->title);
        $this->get(route('market.show', $project->id))->assertOk()->assertSee('noindex', false);
    }

    public function test_private_files_open_only_for_the_client_and_the_market_admin(): void
    {
        Storage::fake('local');
        $client = $this->client();

        $this->actingAs($client)->post(route('market.client.store'), $this->projectForm([
            'files' => [UploadedFile::fake()->create('naghshe.pdf', 200, 'application/pdf')],
        ]))->assertSessionHasNoErrors();
        $file = ProjectFile::query()->sole();
        Storage::disk('local')->assertExists($file->path);

        $this->actingAs($client)->get(route('market.files.download', $file->uuid))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('market.files.download', $file->uuid))->assertNotFound();
        $this->actingAs($this->admin(AdminRole::Content))->get(route('market.files.download', $file->uuid))->assertOk();
        $this->actingAs($this->admin(AdminRole::Finance))->get(route('market.files.download', $file->uuid))->assertNotFound();

        $this->actingAs($client)->post(route('market.client.store'), $this->projectForm([
            'title' => 'پروژه با فایل اجرایی ناامن',
            'files' => [UploadedFile::fake()->create('run.exe', 10)],
        ]))->assertSessionHasErrors('files.0');

        $this->actingAs($client)->delete(route('market.files.destroy', $file->uuid))->assertRedirect();
        Storage::disk('local')->assertMissing($file->path);
    }

    public function test_only_the_market_reviewer_opens_the_queue(): void
    {
        $this->actingAs($this->admin(AdminRole::Content))->get('/'.config('admin.path').'/market-review')
            ->assertOk()->assertSee('data-page-help="filament.fbh.pages.market-review"', false);
        $this->actingAs($this->admin(AdminRole::Finance))->get('/'.config('admin.path').'/market-review')->assertForbidden();
    }

    public function test_the_workspace_list_explains_itself_and_warns_an_unverified_user(): void
    {
        $this->actingAs(User::factory()->create(['mobile_verified_at' => null]))->get(route('market.client.index'))
            ->assertOk()
            ->assertSee('data-page-help="client-projects"', false)
            ->assertSee('اول موبایلتان را تأیید کنید');
    }
}
