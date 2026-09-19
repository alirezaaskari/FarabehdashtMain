<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Contracts\Revisable;
use App\Models\User;
use App\Modules\Core\Services\RevisionRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * نسخه‌بندی محتوا.
 *
 * مدل آزمایشی این‌جا ساخته می‌شود تا Core برای اثبات درستی‌اش به هیچ ماژول
 * محتوایی وابسته نباشد — همان ماژول‌ها هنوز نوشته نشده‌اند.
 */
final class RevisionTest extends TestCase
{
    use RefreshDatabase;

    private RevisionRecorder $revisions;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('fake_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
        });

        $this->revisions = $this->app->make(RevisionRecorder::class);
    }

    private function article(string $title = 'عنوان اول', string $body = 'متن اول'): FakeArticle
    {
        return FakeArticle::query()->create(['title' => $title, 'body' => $body]);
    }

    public function test_the_first_revision_is_version_one(): void
    {
        $revision = $this->revisions->record($this->article());

        $this->assertSame(1, $revision->version);
    }

    public function test_versions_increase_one_by_one_per_content(): void
    {
        $one = $this->article();
        $two = $this->article('عنوان دوم');

        $this->assertSame(1, $this->revisions->record($one)->version);
        $this->assertSame(2, $this->revisions->record($one)->version);
        $this->assertSame(1, $this->revisions->record($two)->version, 'شماره نسخه برای هر محتوا جداست.');
    }

    public function test_only_the_fields_the_model_chooses_are_stored(): void
    {
        $article = $this->article();
        $article->forceFill(['view_count' => 5000])->save();

        $snapshot = $this->revisions->record($article)->snapshot;

        $this->assertSame(['title' => 'عنوان اول', 'body' => 'متن اول'], $snapshot);
        $this->assertArrayNotHasKey('view_count', $snapshot, 'شمارنده بازدید تاریخچه لازم ندارد.');
    }

    public function test_the_previous_text_can_be_read_back_after_an_edit(): void
    {
        $article = $this->article();
        $this->revisions->record($article, 'پیش از بازنویسی');

        $article->forceFill(['title' => 'عنوان تازه', 'body' => 'متن تازه'])->save();

        $previous = $this->revisions->latestFor($article);

        $this->assertNotNull($previous);
        $this->assertSame('عنوان اول', $previous->snapshot['title']);
        $this->assertSame('پیش از بازنویسی', $previous->reason);
    }

    public function test_history_comes_back_newest_first(): void
    {
        $article = $this->article();

        $this->revisions->record($article);
        $article->forceFill(['title' => 'دوم'])->save();
        $this->revisions->record($article);

        $history = $this->revisions->historyFor($article);

        $this->assertCount(2, $history);
        $this->assertSame(2, $history->first()?->version);
    }

    public function test_the_signed_in_user_is_recorded_as_the_author(): void
    {
        $editor = User::factory()->create();
        $this->actingAs($editor);

        $this->assertSame($editor->getKey(), $this->revisions->record($this->article())->author_id);
    }

    public function test_a_revision_written_by_the_system_has_no_author(): void
    {
        $this->assertNull($this->revisions->record($this->article())->author_id);
    }

    public function test_a_non_eloquent_model_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->revisions->record(new NotAModel);
    }

    public function test_content_with_no_revision_yet_has_no_latest(): void
    {
        $this->assertNull($this->revisions->latestFor($this->article()));
    }
}

/** مقاله ساختگی، فقط برای این تست. */
final class FakeArticle extends Model implements Revisable
{
    protected $table = 'fake_articles';

    protected $fillable = ['title', 'body', 'view_count'];

    /** @return array<string, mixed> */
    public function revisionSnapshot(): array
    {
        return ['title' => $this->title, 'body' => $this->body];
    }
}

/** چیزی که Revisable هست ولی مدل Eloquent نیست. */
final class NotAModel implements Revisable
{
    /** @return array<string, mixed> */
    public function revisionSnapshot(): array
    {
        return [];
    }
}
