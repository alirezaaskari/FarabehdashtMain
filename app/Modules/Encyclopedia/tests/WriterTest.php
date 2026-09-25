<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Filament\Resources\Articles\Pages\EditArticle;
use App\Modules\Encyclopedia\Writing\DraftText;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Workspace\Domain\UserNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * نویسنده دانشنامه: پیش‌نویس از میزکار، بازبینی و انتشار فقط با مدیر.
 */
final class WriterTest extends TestCase
{
    use RefreshDatabase;

    private const BODY = "صدا شایع‌ترین عامل زیان‌آور است.\n\n## روش اندازه‌گیری\nبا صداسنج تیپ ۲.\n\n## حد مجاز\n۸۵ دسی‌بل برای ۸ ساعت.";

    public function test_the_writing_pages_need_an_approved_writer_profile(): void
    {
        $this->actingAs(User::factory()->create())->get(route('encyclopedia.writing.index'))->assertForbidden();

        $pending = User::factory()->create();
        $this->app->make(RequestProfileActivation::class)->handle($pending, ProfileType::Writer);
        $this->actingAs($pending->fresh() ?? $pending)->get(route('encyclopedia.writing.index'))->assertForbidden();

        $this->actingAs($this->writer())->get(route('encyclopedia.writing.index'))
            ->assertOk()
            ->assertSee('راهنمای نوشتن برای دانشنامه')
            ->assertSee('نوشته‌های دانشنامه');
    }

    public function test_the_profiles_page_offers_the_writer_profile(): void
    {
        $this->actingAs(User::factory()->create())->get(route('identity.profiles'))
            ->assertOk()
            ->assertSee('نویسنده دانشنامه');
    }

    public function test_a_writer_saves_a_draft_split_into_sections(): void
    {
        $writer = $this->writer();

        $this->actingAs($writer)->post(route('encyclopedia.writing.store'), $this->input())->assertRedirect();

        $article = Article::query()->sole();
        $this->assertSame(ArticleStatus::Draft, $article->status);
        $this->assertSame($writer->id, $article->author_id);
        $this->assertStringStartsWith('draft-', $article->slug);
        $this->assertSame(['مقدمه', 'روش اندازه‌گیری', 'حد مجاز'], $article->sections->pluck('heading')->all());
        $this->assertSame('ISO 9612', $article->references->first()?->title);
        $this->assertSame(2009, $article->references->first()?->year);

        $this->get(route('encyclopedia.writing.edit', $article->uuid))
            ->assertOk()
            ->assertSee('## روش اندازه‌گیری')
            ->assertSee('ارسال برای بازبینی');
    }

    public function test_submitting_sends_it_to_the_review_queue_and_locks_it(): void
    {
        $writer = $this->writer();
        $article = $this->draft($writer);

        $this->actingAs($writer)->post(route('encyclopedia.writing.submit', $article->uuid))
            ->assertRedirect(route('encyclopedia.writing.index'));

        $this->assertSame(ArticleStatus::InReview, $article->refresh()->status);

        $this->put(route('encyclopedia.writing.update', $article->uuid), $this->input(['title' => 'تغییر پنهانی']))
            ->assertSessionHasErrors('body');
        $this->assertNotSame('تغییر پنهانی', $article->refresh()->title);
    }

    public function test_another_writer_cannot_open_the_draft(): void
    {
        $article = $this->draft($this->writer());

        $this->actingAs($this->writer())->get(route('encyclopedia.writing.edit', $article->uuid))->assertNotFound();
    }

    public function test_the_admin_returns_it_with_a_note_and_the_writer_fixes_and_resubmits(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));
        $writer = $this->writer();
        $article = $this->draft($writer);
        $this->actingAs($writer)->post(route('encyclopedia.writing.submit', $article->uuid));

        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Content);
        $this->actingAs($admin->fresh() ?? $admin);

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->callAction('return', ['note' => 'منبع دوم را اضافه کنید.'])
            ->assertHasNoActionErrors();

        $article->refresh();
        $this->assertSame(ArticleStatus::Draft, $article->status);
        $this->assertSame('منبع دوم را اضافه کنید.', $article->review_note);
        $this->assertSame(1, UserNotification::query()->where('user_id', $writer->id)->where('kind', 'encyclopedia.article_returned')->count());

        $this->actingAs($writer)->get(route('encyclopedia.writing.edit', $article->uuid))->assertSee('منبع دوم را اضافه کنید.');
        $this->put(route('encyclopedia.writing.update', $article->uuid), $this->input())->assertSessionHasNoErrors();
        $this->post(route('encyclopedia.writing.submit', $article->uuid));

        $this->assertNull($article->refresh()->review_note);
        $this->assertSame(ArticleStatus::InReview, $article->status);
    }

    public function test_publishing_tells_the_writer(): void
    {
        $writer = $this->writer();
        $article = $this->draft($writer);
        $article->forceFill(['reviewer_id' => User::factory()->create()->id, 'reviewed_at' => now()])->save();

        $this->app->make(PublishArticle::class)->handle($article->refresh(), User::factory()->create()->id);

        $this->assertSame(1, UserNotification::query()->where('user_id', $writer->id)->where('kind', 'encyclopedia.article_published')->count());
    }

    public function test_the_writer_workspace_view_shows_their_writing_card(): void
    {
        $writer = $this->writer();
        $this->draft($writer);

        $this->actingAs($writer)->post(route('workspace.view'), ['view' => 'writer']);

        $this->get(route('workspace.dashboard'))
            ->assertOk()
            ->assertSee('سنجش صدای محیط کار')
            ->assertSee('نوشته تازه');
    }

    public function test_draft_text_round_trips(): void
    {
        $sections = DraftText::sections(self::BODY);

        $this->assertCount(3, $sections);
        $this->assertSame(['heading' => 'حد مجاز', 'body' => '۸۵ دسی‌بل برای ۸ ساعت.'], $sections[2]);

        $refs = DraftText::references("ISO 9612 | ISO | | ۲۰۰۹\n\nعنوان تنها");
        $this->assertSame(2009, $refs[0]['year']);
        $this->assertNull($refs[0]['edition']);
        $this->assertSame('عنوان تنها', $refs[1]['title']);
    }

    private function writer(): User
    {
        $user = User::factory()->create();
        $profile = $this->app->make(RequestProfileActivation::class)->handle($user, ProfileType::Writer);
        $this->app->make(ReviewProfileRequest::class)->approve($profile, User::factory()->create());

        return $user->fresh() ?? $user;
    }

    private function draft(User $writer): Article
    {
        $this->actingAs($writer)->post(route('encyclopedia.writing.store'), $this->input())->assertRedirect();

        return Article::query()->where('author_id', $writer->id)->latest('id')->firstOrFail();
    }

    /** @return array<string, string> */
    private function input(array $overrides = []): array
    {
        return array_replace([
            'title' => 'سنجش صدای محیط کار',
            'type' => 'guide',
            'summary' => 'راهنمای گام‌به‌گام سنجش صدا.',
            'body' => self::BODY,
            'references' => 'ISO 9612 | ISO | | 2009 | https://www.iso.org/standard/41718.html',
        ], $overrides);
    }
}
