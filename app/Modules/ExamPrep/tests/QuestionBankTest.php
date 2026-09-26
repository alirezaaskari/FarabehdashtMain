<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\ExamPrep\Actions\ImportQuestions;
use App\Modules\ExamPrep\Domain\Enums\QuestionStatus;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Modules\ExamPrep\Filament\Pages\ExamPacksPage;
use App\Modules\ExamPrep\Filament\Pages\ExamQuestionsPage;
use App\Modules\ExamPrep\Services\QuestionCsv;
use App\Modules\ExamPrep\Services\QuestionDraft;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Workspace\Domain\UserNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/** بانک سؤال: بسته، ورود CSV، نوشتن مدرس و صف تأیید مدیر. */
final class QuestionBankTest extends TestCase
{
    use ExamFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('fbh'));
    }

    public function test_a_pack_that_promises_a_pass_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->pack(['description' => 'قبولی تضمینی در آزمون استخدامی با این بسته.'], publish: false);
    }

    public function test_a_pack_cannot_be_free(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->pack(['price' => '0'], publish: false);
    }

    public function test_a_pack_without_published_questions_cannot_be_published(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->pack(publish: true, questions: 0);
    }

    public function test_the_free_sample_is_capped_at_ten_questions(): void
    {
        $pack = $this->pack(questions: 10, samples: 10);

        $this->expectException(InvalidArgumentException::class);

        $this->question($pack, 'صدا', 'سؤال یازدهم نمونه', sample: true);
    }

    public function test_draft_rejects_bad_choices_and_external_links(): void
    {
        foreach ([
            fn () => QuestionDraft::make('صدا', 'آسان', 'متن سؤال کامل', ['تنها گزینه'], 1),
            fn () => QuestionDraft::make('صدا', 'آسان', 'متن سؤال کامل', ['الف', 'الف'], 1),
            fn () => QuestionDraft::make('صدا', 'آسان', 'متن سؤال کامل', ['الف', 'ب', ''], 3),
            fn () => QuestionDraft::make('صدا', 'خیلی سخت', 'متن سؤال کامل', ['الف', 'ب'], 1),
            fn () => QuestionDraft::make('صدا', 'آسان', 'متن سؤال کامل', ['الف', 'ب'], 1, referencePath: 'https://example.com/x'),
        ] as $i => $make) {
            try {
                $make();
                $this->fail('ردیف '.$i.' باید رد شود.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame('/tools', QuestionDraft::make('صدا', '۲', 'متن سؤال کامل', ['الف', 'ب'], 2, referencePath: url('/tools'))->referencePath);
    }

    public function test_the_csv_template_imports_cleanly(): void
    {
        $pack = $this->pack(questions: 0, publish: false);
        $admin = User::factory()->create();

        $result = $this->app->make(ImportQuestions::class)->handle($pack, $this->app->make(QuestionCsv::class)->template(), $admin->id);

        $this->assertSame(['imported' => 2, 'errors' => []], $result);
        $this->assertSame(2, PrepQuestion::query()->where('status', QuestionStatus::Published)->count());
        $this->assertSame(1, PrepQuestion::query()->where('is_sample', true)->count());
    }

    public function test_an_import_with_one_bad_row_writes_nothing(): void
    {
        $pack = $this->pack(questions: 0, publish: false);
        $admin = User::factory()->create();
        $csv = implode("\n", [
            implode(',', QuestionCsv::COLUMNS),
            'صدا,آسان,واحد تراز فشار صوت کدام است؟,دسی‌بل,هرتز,,,,1,,,,0',
            'موضوع ناشناخته,آسان,سؤالی با موضوع اشتباه,الف,ب,,,,1,,,,0',
        ]);

        $preview = $this->app->make(ImportQuestions::class)->handle($pack, $csv, $admin->id, dryRun: true);
        $this->assertSame(0, $preview['imported']);
        $this->assertSame(3, $preview['errors'][0]['line']);

        $this->app->make(ImportQuestions::class)->handle($pack, $csv, $admin->id);
        $this->assertSame(0, PrepQuestion::query()->count());
    }

    public function test_an_instructor_question_waits_for_review_and_the_author_hears_back(): void
    {
        $pack = $this->pack();
        $instructor = $this->instructor();
        $topic = $pack->topics()->where('title', 'صدا')->sole();

        $this->actingAs($instructor)->get(route('exam_prep.writer.create'))->assertOk()->assertSee($pack->title);

        $this->post(route('exam_prep.writer.store'), [
            'topic' => $topic->id,
            'difficulty' => 'hard',
            'body' => 'حد مجاز مواجهه شغلی با صدا برای هشت ساعت چند دسی‌بل است؟',
            'choices' => ['۸۵', '۹۰', '۸۰', '', ''],
            'correct' => 1,
            'reference_path' => '/encyclopedia',
        ])->assertRedirect(route('exam_prep.writer.index'));

        $question = PrepQuestion::query()->where('author_user_id', $instructor->id)->sole();
        $this->assertSame(QuestionStatus::Pending, $question->status);

        $admin = $this->admin();
        Livewire::actingAs($admin)->test(ExamQuestionsPage::class)
            ->call('reject', $question->id)
            ->assertNotified()
            ->set('notes.'.$question->id, 'گزینه دوم هم در برخی منابع درست است.')
            ->call('reject', $question->id)
            ->assertNotified();

        $this->assertSame(QuestionStatus::Rejected, $question->fresh()?->status);
        $this->assertTrue(UserNotification::query()->where('user_id', $instructor->id)->where('kind', 'exam_prep.question_rejected')->exists());

        $this->actingAs($instructor)->get(route('exam_prep.writer.index'))->assertSee('گزینه دوم هم در برخی منابع درست است.');
    }

    public function test_only_instructors_write_questions(): void
    {
        $this->actingAs(User::factory()->create())->get(route('exam_prep.writer.index'))->assertForbidden();
    }

    public function test_the_admin_builds_and_publishes_a_pack_from_the_panel(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(ExamPacksPage::class)
            ->set('form.title', 'بسته کارشناسی ارشد')
            ->set('form.slug', 'msc-prep')
            ->set('form.exam_name', 'کنکور کارشناسی ارشد بهداشت حرفه‌ای')
            ->set('form.description', 'مرور سؤال‌های سال‌های گذشته به تفکیک درس.')
            ->set('form.price', '۲۵۰٬۰۰۰')
            ->set('form.topics', "سم‌شناسی\nصدا")
            ->call('save')
            ->assertNotified()
            ->call('publish', 1)
            ->assertNotified('انجام نشد');

        $this->assertDatabaseHas('exam_packs', ['slug' => 'msc-prep', 'price_toman' => 250_000, 'status' => 'draft']);
    }

    private function instructor(): User
    {
        $user = User::factory()->create();
        $profile = $this->app->make(RequestProfileActivation::class)->handle($user, ProfileType::Instructor);
        $this->app->make(ReviewProfileRequest::class)->approve($profile, User::factory()->create());

        return $user->fresh() ?? $user;
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Super);

        return $admin->fresh() ?? $admin;
    }
}
