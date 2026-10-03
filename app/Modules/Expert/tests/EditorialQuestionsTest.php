<?php

declare(strict_types=1);

namespace App\Modules\Expert\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Expert\Actions\ReviewAnswer;
use App\Modules\Expert\Actions\SubmitAnswer;
use App\Modules\Expert\Actions\SyncEditorialQuestions;
use App\Modules\Expert\Domain\Enums\QuestionTopic;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Modules\Expert\Editorial\EditorialEntry;
use App\Modules\Expert\Editorial\EditorialQuestions;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Workspace\Domain\UserNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پرسش و پاسخ‌های نمونه تحریریه: هر حوزه پر است، اجرای دوباره چیزی را
 * تکرار نمی‌کند و هیچ‌جا به‌نام مشاور تأییدشده یا کاربر واقعی دیده نمی‌شوند.
 */
final class EditorialQuestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_topic_has_well_formed_entries_with_sources(): void
    {
        $entries = (new EditorialQuestions)->entries();
        $limits = (array) config('expert.limits');

        $keys = array_map(static fn (EditorialEntry $entry): string => $entry->key, $entries);
        $this->assertSame($keys, array_values(array_unique($keys)), 'کلید تکراری نشانی صفحه را خراب می‌کند.');

        foreach (QuestionTopic::cases() as $topic) {
            $count = count(array_filter($entries, static fn (EditorialEntry $entry): bool => $entry->topic === $topic));
            $this->assertGreaterThanOrEqual(3, $count, $topic->label());
        }

        foreach ($entries as $entry) {
            $this->assertNotEmpty($entry->sources, $entry->key);
            $this->assertGreaterThanOrEqual($limits['title_min'], mb_strlen($entry->title), $entry->key);
            $this->assertLessThanOrEqual($limits['title_max'], mb_strlen($entry->title), $entry->key);
            $this->assertGreaterThanOrEqual($limits['body_min'], mb_strlen($entry->question), $entry->key);
            $this->assertLessThanOrEqual($limits['answer_max'], mb_strlen($entry->answerBody()), $entry->key);
        }
    }

    public function test_sync_publishes_once_and_follows_the_code(): void
    {
        $total = count((new EditorialQuestions)->entries());

        $this->artisan('fbh:expert:editorial')->assertSuccessful();

        $this->assertSame($total, ExpertQuestion::query()->listed()->whereNull('user_id')->count());
        $this->assertSame($total, ExpertAnswer::query()->published()->whereNull('user_id')->count());

        $this->assertSame(['created' => 0, 'updated' => 0], $this->app->make(SyncEditorialQuestions::class)->handle());
        $this->assertSame($total, ExpertQuestion::query()->count());

        // متن اصلاح‌شده در کد با اجرای بعدی به سایت می‌رسد، بی‌آنکه نشانی عوض شود.
        $uuid = SyncEditorialQuestions::questionUuid('noise-combining-sources');
        ExpertQuestion::query()->where('uuid', $uuid)->update(['title' => 'عنوان کهنه']);

        $this->assertSame(['created' => 0, 'updated' => 1], $this->app->make(SyncEditorialQuestions::class)->handle());
        $this->assertNotSame('عنوان کهنه', ExpertQuestion::query()->where('uuid', $uuid)->value('title'));
    }

    public function test_editorial_content_is_labelled_as_such(): void
    {
        $this->app->make(SyncEditorialQuestions::class)->handle();
        $question = ExpertQuestion::query()->where('uuid', SyncEditorialQuestions::questionUuid('noise-combining-sources'))->sole();

        $this->get(route('expert.index', ['topic' => 'noise']))->assertOk()->assertSee($question->title)->assertSee('نمونه تحریریه');

        $this->get(route('expert.show', $question->uuid))
            ->assertOk()
            ->assertSee('پرسش نمونه از تحریریه فرابهداشت')
            ->assertSee('پاسخ تحریریه، مستند به منابع')
            ->assertSee('منابع:')
            ->assertDontSee('مشاور تأییدشده در فرابهداشت')
            ->assertSee('"@type":"Organization"', false);
    }

    public function test_a_consultant_can_add_an_answer_without_a_notice_to_nobody(): void
    {
        $this->app->make(SyncEditorialQuestions::class)->handle();
        $question = ExpertQuestion::query()->where('uuid', SyncEditorialQuestions::questionUuid('heat-water-intake'))->sole();

        $consultant = User::factory()->create(['name' => 'مشاور آزمایشی']);
        UserProfile::factory()->for($consultant)->ofType(ProfileType::Consultant)->active()->create();
        $consultant = $consultant->fresh() ?? $consultant;

        // پرسش تحریریه پاسخ دارد و پرسش‌کننده‌ای منتظرش نیست؛ در صف مشاور نمی‌آید.
        $this->actingAs($consultant)->get(route('expert.queue'))->assertOk()->assertDontSee($question->title);

        $answer = $this->app->make(SubmitAnswer::class)->handle(
            $consultant,
            $question,
            'در کار سنگین و طولانی، علاوه بر آب، نوشیدنی دارای الکترولیت در دسترس باشد و پایش وزن پیش و پس از شیفت کم‌آبی را نشان می‌دهد.',
        );
        $this->app->make(ReviewAnswer::class)->publish($answer, $this->admin()->id);

        $this->assertSame(['expert.answer_approved'], UserNotification::query()->pluck('kind')->all());
        $this->get(route('expert.show', $question->uuid))->assertOk()->assertSee('مشاور تأییدشده در فرابهداشت');
    }

    private function admin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, AdminRole::Content);

        return $user->fresh() ?? $user;
    }
}
