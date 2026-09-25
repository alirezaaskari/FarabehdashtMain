<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Core\Domain\AuditLog;
use App\Modules\Core\Domain\ContentRevision;
use App\Modules\Encyclopedia\Actions\SaveArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Filament\Resources\Articles\Pages\CreateArticle;
use App\Modules\Encyclopedia\Filament\Resources\Articles\Pages\EditArticle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ویرایشگر دانشنامه در پنل: بدون آن در production هیچ محتوای تازه‌ای نوشته
 * نمی‌شد، چون تنها سازنده دستور seed بود.
 */
final class ArticleEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('fbh'));
    }

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    /** @return array<string, mixed> */
    private function data(array $overrides = []): array
    {
        return array_replace([
            'title' => 'سنجش صدای محیط کار',
            'slug' => 'workplace-noise',
            'type' => 'guide',
            'summary' => 'راهنمای گام‌به‌گام سنجش صدا.',
            'reviewer_id' => null,
            'reviewed_at' => null,
            'sections' => [
                ['heading' => 'مقدمه', 'body' => "بند اول.\n\nبند دوم.", 'note' => null, 'tool_slug' => null],
                ['heading' => 'روش', 'body' => 'متن روش.', 'note' => 'نکته', 'tool_slug' => null],
            ],
            'references' => [
                ['title' => 'ISO 9612', 'publisher' => 'ISO', 'edition' => null, 'year' => 2009, 'url' => null],
            ],
        ], $overrides);
    }

    public function test_a_content_admin_opens_the_editor_and_a_finance_admin_cannot(): void
    {
        $path = '/'.config('admin.path').'/articles';

        $this->actingAs($this->adminWith(AdminRole::Content))->get($path)->assertOk()->assertSee('محتوای تازه');
        $this->actingAs($this->adminWith(AdminRole::Content))->get($path.'/create')->assertOk()->assertSee('بازبینی علمی');
        $this->actingAs($this->adminWith(AdminRole::Finance))->get($path)->assertForbidden();
    }

    public function test_creating_from_the_panel_saves_a_draft_with_its_sections_and_a_revision(): void
    {
        $admin = $this->adminWith(AdminRole::Content);
        $this->actingAs($admin);

        Livewire::test(CreateArticle::class)
            ->fillForm($this->data())
            ->call('create')
            ->assertHasNoFormErrors();

        $article = Article::query()->where('slug', 'workplace-noise')->sole();

        $this->assertSame(ArticleStatus::Draft, $article->status);
        $this->assertSame($admin->id, $article->author_id);
        $this->assertSame(['مقدمه', 'روش'], $article->sections->pluck('heading')->all());
        $this->assertSame(['بند اول.', 'بند دوم.'], $article->sections->first()?->paragraphs());
        $this->assertSame(2009, $article->references->sole()->year);

        $revision = ContentRevision::query()->where('revisable_id', $article->id)->sole();
        $this->assertSame('ساخت از پنل', $revision->reason);
        AuditLog::query()->where('action', 'encyclopedia.article_created')->sole();
    }

    public function test_publishing_from_the_editor_still_needs_a_reviewer(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content));
        $article = $this->app->make(SaveArticle::class)->handle(null, $this->data(), null);

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->callAction('publish');

        $this->assertSame(ArticleStatus::Draft, $article->refresh()->status);

        $reviewer = User::factory()->create(['name' => 'دکتر بازبین']);
        $this->app->make(SaveArticle::class)->handle($article, $this->data([
            'reviewer_id' => $reviewer->id,
            'reviewed_at' => '2026-09-20',
        ]), null);

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->callAction('publish');

        $published = $article->refresh();
        $this->assertSame(ArticleStatus::Published, $published->status);
        $this->assertNotNull($published->review_due_at);
        $this->assertSame(['ساخت از پنل', 'ویرایش از پنل', 'انتشار'], ContentRevision::query()
            ->where('revisable_id', $article->id)->orderBy('version')->pluck('reason')->all());
    }

    public function test_the_address_of_a_published_article_does_not_change(): void
    {
        $article = $this->app->make(SaveArticle::class)->handle(null, $this->data(), null);
        $article->forceFill(['status' => ArticleStatus::Published])->save();

        $saved = $this->app->make(SaveArticle::class)->handle($article, $this->data([
            'slug' => 'something-else',
            'title' => 'عنوان تازه',
        ]), null);

        $this->assertSame('workplace-noise', $saved->slug);
        $this->assertSame('عنوان تازه', $saved->title);
    }

    public function test_swapping_two_sections_keeps_positions_unique(): void
    {
        $article = $this->app->make(SaveArticle::class)->handle(null, $this->data(), null);
        $sections = $this->data()['sections'];

        $saved = $this->app->make(SaveArticle::class)->handle($article, $this->data([
            'sections' => array_reverse($sections),
        ]), null);

        $this->assertSame(['روش', 'مقدمه'], $saved->sections->pluck('heading')->all());
        $this->assertSame([1, 2], $saved->sections->pluck('position')->all());
    }

    public function test_the_edit_page_shows_what_was_saved(): void
    {
        $article = $this->app->make(SaveArticle::class)->handle(null, $this->data(), null);

        $this->actingAs($this->adminWith(AdminRole::Content));

        $page = Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->assertFormSet(['title' => 'سنجش صدای محیط کار', 'slug' => 'workplace-noise'])
            ->assertActionVisible('submit');

        $this->assertSame(['مقدمه', 'روش'], array_values(array_column($page->get('data.sections'), 'heading')));
        $this->assertSame(['ISO 9612'], array_values(array_column($page->get('data.references'), 'title')));
    }

    public function test_the_address_must_be_latin_and_unique(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content));
        $this->app->make(SaveArticle::class)->handle(null, $this->data(), null);

        Livewire::test(CreateArticle::class)
            ->fillForm($this->data(['slug' => 'workplace-noise']))
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);

        Livewire::test(CreateArticle::class)
            ->fillForm($this->data(['slug' => 'صدا']))
            ->call('create')
            ->assertHasFormErrors(['slug' => 'regex']);
    }
}
