<?php

declare(strict_types=1);

namespace App\Modules\Expert\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Expert\Actions\AskQuestion;
use App\Modules\Expert\Actions\ReviewAnswer;
use App\Modules\Expert\Actions\ReviewQuestion;
use App\Modules\Expert\Actions\SubmitAnswer;
use App\Modules\Expert\Domain\Enums\QuestionTopic;
use App\Modules\Expert\Domain\Enums\QuestionVisibility;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Modules\Expert\Filament\Pages\ExpertReviewPage;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Workspace\Domain\UserNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * پرسش از متخصص (بخش ۱۸-۳): تأیید مدیر پیش از هر انتشار، ناشناس ماندن
 * پرسش‌کننده (DEC-41)، اولویت Pro (DEC-40) و نشان مشاور (DEC-42).
 */
final class ExpertFlowTest extends TestCase
{
    use RefreshDatabase;

    private const BODY = 'در سالن پرس، تراز صدای معادل ۸ ساعته ۹۲ دسی‌بل اندازه گرفته‌ایم. کدام حفاظ شنوایی کافی است؟';

    private const ANSWER = 'با تراز ۹۲ دسی‌بل، حفاظی لازم است که پس از اعمال ضریب کاهش واقعی، تراز مؤثر را زیر ۸۵ دسی‌بل ببرد. روش محاسبه NRR را در راهنما ببینید.';

    public function test_a_question_waits_for_the_admin_before_anyone_else_sees_it(): void
    {
        $asker = User::factory()->create();

        $this->actingAs($asker)->post(route('expert.store'), [
            'topic' => 'noise',
            'visibility' => 'public',
            'title' => 'حفاظ شنوایی برای سالن پرس',
            'body' => self::BODY,
        ])->assertRedirect();

        $question = ExpertQuestion::query()->sole();
        $this->assertSame(ReviewStatus::Pending, $question->status);

        $this->actingAs($asker)->get(route('expert.show', $question->uuid))->assertOk()->assertSee('در انتظار تأیید مدیر');
        $this->actingAs(User::factory()->create())->get(route('expert.show', $question->uuid))->assertNotFound();
        $this->get(route('expert.index'))->assertDontSee('حفاظ شنوایی برای سالن پرس');
    }

    public function test_the_admin_publishes_a_question_and_the_asker_hears_about_it(): void
    {
        $question = $this->ask();

        $this->actingAs($this->admin());
        Livewire::test(ExpertReviewPage::class)->call('publishQuestion', $question->id)->assertSet('questions', []);

        $this->assertSame(ReviewStatus::Published, $question->fresh()?->status);
        $this->assertSame('expert.question_published', UserNotification::query()->where('user_id', $question->user_id)->sole()->kind);

        $this->get(route('expert.index'))->assertSee($question->title);
    }

    public function test_a_rejection_needs_a_reason(): void
    {
        $question = $this->ask();

        $this->expectException(RuntimeException::class);

        $this->app->make(ReviewQuestion::class)->reject($question, $this->admin()->id, '  ');
    }

    public function test_only_an_approved_consultant_answers_and_only_after_review(): void
    {
        $question = $this->published();

        $this->actingAs(User::factory()->create())
            ->post(route('expert.answers.store', $question->uuid), ['answer' => self::ANSWER])
            ->assertForbidden();

        $consultant = $this->consultant('دکتر مشاور');

        $this->actingAs($consultant)
            ->post(route('expert.answers.store', $question->uuid), ['answer' => self::ANSWER])
            ->assertRedirect(route('expert.show', $question->uuid));

        $answer = ExpertAnswer::query()->sole();
        $this->assertSame(ReviewStatus::Pending, $answer->status);
        $this->get(route('expert.show', $question->uuid))->assertDontSee('دکتر مشاور');

        $this->app->make(ReviewAnswer::class)->publish($answer, $this->admin()->id);

        $this->get(route('expert.show', $question->uuid))
            ->assertSee('دکتر مشاور')
            ->assertSee('مشاور تأییدشده در فرابهداشت')
            ->assertSee('این پاسخ نظر کارشناسی است؛ تشخیص پزشکی یا تأیید انطباق قانونی نیست.');

        $this->assertTrue(UserNotification::query()->where('user_id', $question->user_id)->where('kind', 'expert.answer_published')->exists());
    }

    public function test_a_consultant_cannot_answer_their_own_question(): void
    {
        $consultant = $this->consultant();
        $question = $this->published($consultant);

        $this->expectException(RuntimeException::class);

        $this->app->make(SubmitAnswer::class)->handle($consultant, $question, self::ANSWER);
    }

    public function test_a_returned_answer_is_fixed_and_sent_again(): void
    {
        $question = $this->published();
        $consultant = $this->consultant();
        $answer = $this->app->make(SubmitAnswer::class)->handle($consultant, $question, self::ANSWER);
        $this->app->make(ReviewAnswer::class)->reject($answer, $this->admin()->id, 'منبع را بنویسید.');

        $this->actingAs($consultant)->get(route('expert.show', $question->uuid))->assertSee('منبع را بنویسید.');

        $again = $this->app->make(SubmitAnswer::class)->handle($consultant, $question, self::ANSWER.' منبع: ISO 4869-2.');

        $this->assertSame($answer->id, $again->id);
        $this->assertSame(ReviewStatus::Pending, $again->status);
        $this->assertNull($again->review_note);
    }

    public function test_the_asker_marks_the_best_answer(): void
    {
        $question = $this->published();
        $answer = $this->answered($question);

        $this->actingAs(User::factory()->create())
            ->post(route('expert.accept', [$question->uuid, $answer->uuid]))
            ->assertSessionHasErrors('accept');

        $this->actingAs($question->asker)
            ->post(route('expert.accept', [$question->uuid, $answer->uuid]))
            ->assertRedirect(route('expert.show', $question->uuid));

        $this->assertTrue($question->fresh()?->isAnswered());
        $this->get(route('expert.show', $question->uuid))->assertSee('بهترین پاسخ به انتخاب پرسش‌کننده');
        $this->assertTrue(UserNotification::query()->where('user_id', $answer->user_id)->where('kind', 'expert.answer_accepted')->exists());
    }

    public function test_the_asker_is_never_named(): void
    {
        $asker = User::factory()->create(['name' => 'نام محرمانه پرسشگر']);
        $question = $this->published($asker);
        $this->answered($question);

        $this->get(route('expert.show', $question->uuid))->assertOk()->assertDontSee('نام محرمانه پرسشگر');
        $this->get(route('expert.index'))->assertDontSee('نام محرمانه پرسشگر');
    }

    public function test_a_private_question_stays_between_asker_consultants_and_admin(): void
    {
        $question = $this->published(visibility: QuestionVisibility::Private);
        $url = route('expert.show', $question->uuid);

        $this->get($url)->assertNotFound();
        $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
        $this->actingAs($this->consultant())->get($url)->assertOk();
        $this->actingAs($question->asker)->get($url)->assertOk()->assertSee('noindex', false);

        $this->get(route('expert.index'))->assertDontSee($question->title);
        $this->get(route('workspace.search', ['q' => 'حفاظ']))->assertDontSee($question->title);
        $this->get(route('core.sitemap.file', 'expert'))->assertDontSee($question->uuid);
    }

    public function test_a_public_answered_question_is_a_qa_page_in_the_sitemap(): void
    {
        $question = $this->published();
        $this->answered($question);

        $this->get(route('expert.show', $question->uuid))->assertSee('"@type":"QAPage"', false);
        $this->get(route('core.sitemap.file', 'expert'))->assertSee($question->uuid);
        $this->get(route('workspace.search', ['q' => 'حفاظ']))->assertSee($question->title);
    }

    public function test_pro_questions_come_first_in_the_consultant_queue(): void
    {
        $free = $this->published();

        $subscriber = User::factory()->create();
        Subscription::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $subscriber->getKey(),
            'started_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addMonth(),
        ]);
        $pro = $this->published($subscriber);

        $this->assertFalse($free->priority);
        $this->assertTrue($pro->priority);

        $this->actingAs($this->consultant())
            ->get(route('expert.queue'))
            ->assertOk()
            ->assertSeeInOrder([$pro->title, $free->title]);
    }

    public function test_the_review_page_is_for_content_admins_only(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get('/'.config('admin.path').'/expert-review')
            ->assertForbidden();

        $this->ask();

        $this->actingAs($this->admin())
            ->get('/'.config('admin.path').'/expert-review')
            ->assertOk()
            ->assertSee('حفاظ شنوایی');
    }

    public function test_workspace_pages_list_questions_and_the_queue_is_for_consultants(): void
    {
        $question = $this->ask();

        $this->actingAs($question->asker)->get(route('expert.mine'))->assertOk()->assertSee($question->title);
        $this->actingAs($question->asker)->get(route('expert.queue'))->assertForbidden();
        $this->actingAs($question->asker)->get(route('expert.create'))->assertOk();
    }

    private function ask(?User $asker = null, QuestionVisibility $visibility = QuestionVisibility::Public): ExpertQuestion
    {
        return $this->app->make(AskQuestion::class)->handle(
            $asker ?? User::factory()->create(),
            QuestionTopic::Noise,
            $visibility,
            'حفاظ شنوایی برای سالن پرس '.Str::random(4),
            self::BODY,
        );
    }

    private function published(?User $asker = null, QuestionVisibility $visibility = QuestionVisibility::Public): ExpertQuestion
    {
        $question = $this->ask($asker, $visibility);
        $this->app->make(ReviewQuestion::class)->publish($question, $this->admin()->id);

        return $question->fresh() ?? $question;
    }

    private function answered(ExpertQuestion $question): ExpertAnswer
    {
        $answer = $this->app->make(SubmitAnswer::class)->handle($this->consultant(), $question, self::ANSWER);

        return $this->app->make(ReviewAnswer::class)->publish($answer, $this->admin()->id);
    }

    private function consultant(string $name = 'مشاور آزمایشی'): User
    {
        $user = User::factory()->create(['name' => $name]);
        UserProfile::factory()->for($user)->ofType(ProfileType::Consultant)->active()->create();

        return $user->fresh() ?? $user;
    }

    private function admin(): User
    {
        return $this->adminWith(AdminRole::Content);
    }

    private function adminWith(AdminRole $role): User
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
