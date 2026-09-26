<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Tests;

use App\Contracts\MediaLibrary;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Actions\SaveArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Filament\Resources\Articles\Pages\EditArticle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** تصویر بخش مقاله از کتابخانه مدیا (بخش ۱۸-۱۱). */
final class ArticleImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('fbh'));
    }

    public function test_a_section_image_shows_with_its_alt_and_caption(): void
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'fbh');
        imagejpeg(imagecreatetruecolor(800, 400), $path);
        $media = $this->app->make(MediaLibrary::class)->storeImage($path, 'meter.jpg', null);

        $article = $this->publish([
            'image' => (string) $media->id,
            'image_alt' => 'صداسنج روی سه‌پایه کنار دستگاه پرس',
            'image_caption' => 'میکروفون در ارتفاع گوش کارگر.',
        ]);

        $this->assertSame($media->id, $article->sections->first()?->image_id);

        $this->get(route('encyclopedia.show', $article->slug))
            ->assertOk()
            ->assertSee('<img src="'.$media->url.'" alt="صداسنج روی سه‌پایه کنار دستگاه پرس"', false)
            ->assertSee('width="800" height="400"', false)
            ->assertSee('میکروفون در ارتفاع گوش کارگر.');

        $this->get(route('encyclopedia.print', $article->slug))->assertOk()->assertSee($media->url, false);
    }

    public function test_the_editor_uploads_through_the_library(): void
    {
        $article = $this->publish([]);
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Content);
        $this->actingAs($admin->fresh() ?? $admin);

        $page = Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()]);
        $key = array_key_first($page->get('data.sections'));

        $page->set("data.sections.{$key}.image", UploadedFile::fake()->image('photo.jpg', 2000, 1000))
            ->set("data.sections.{$key}.image_alt", 'نمونه')
            ->call('save')
            ->assertHasNoErrors();

        $section = $article->refresh()->sections->first();
        $this->assertNotNull($section?->image_id);
        $this->assertSame(1600, $this->app->make(MediaLibrary::class)->find((int) $section->image_id)?->width);
    }

    public function test_an_image_needs_its_alt_text(): void
    {
        $article = $this->publish([]);
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Content);
        $this->actingAs($admin->fresh() ?? $admin);

        $page = Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()]);
        $key = array_key_first($page->get('data.sections'));

        $page->set("data.sections.{$key}.image", UploadedFile::fake()->image('photo.jpg', 200, 100))
            ->call('save')
            ->assertHasErrors("data.sections.{$key}.image_alt");
    }

    /** @param  array<string, mixed>  $image */
    private function publish(array $image): Article
    {
        $reviewer = User::factory()->create();
        $article = $this->app->make(SaveArticle::class)->handle(null, [
            'title' => 'سنجش صدا',
            'slug' => 'noise-image',
            'type' => 'guide',
            'summary' => 'راهنما.',
            'reviewer_id' => $reviewer->id,
            'reviewed_at' => '2026-09-20',
            'sections' => [['heading' => 'روش', 'body' => 'متن روش.', 'note' => null, 'tool_slug' => null, ...$image]],
            'references' => [['title' => 'ISO 9612', 'publisher' => 'ISO', 'edition' => null, 'year' => 2009, 'url' => null]],
        ], null);

        return $this->app->make(PublishArticle::class)->handle($article)->load('sections');
    }
}
