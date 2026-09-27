<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Tests;

use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Modules\ExamPrep\Domain\Enums\AttemptMode;
use App\Modules\ExamPrep\Domain\PrepAttempt;
use App\Modules\ExamPrep\Tests\ExamFixtures;
use App\Modules\Expert\Domain\Enums\QuestionTopic;
use App\Modules\Expert\Domain\Enums\QuestionVisibility;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Modules\Jobs\Domain\Passport;
use App\Modules\Jobs\Filament\Pages\JobPricingPage;
use App\Modules\Jobs\Services\SkillPassport;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use App\Support\Taxonomy\TermData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * گذرنامه مهارتی (۲۰-۳): بخش «ثبت‌شده» از منبع‌های ماژول‌ها، آستانه آزمون
 * پنل (DEC-70)، بخش اظهاری بی‌نشان، و صفحه اشتراکی اختیاری noindex (DEC-69).
 */
final class SkillPassportTest extends TestCase
{
    use ExamFixtures;
    use JobFixtures;
    use RefreshDatabase;

    public function test_the_verified_section_gathers_site_records_and_maps_them_to_skills(): void
    {
        $user = User::factory()->create();
        $this->completedCourse($user, 'ایمنی کار در ارتفاع');
        $this->issuedReport($user, ['noise-dose v1.0', 'noise-dose v1.0', 'wbgt-indoor v1.0']);
        $this->issuedReport($user, ['noise-dose v1.0']);
        $this->issuedReport($user, ['twa-ppm v1.0'], ReportStatus::Revoked);
        $this->publishedAnswer($user, QuestionTopic::Ventilation);

        $pack = $this->pack();
        $this->examAttempt($user, $pack->id, correct: 4, total: 5);

        $verified = $this->app->make(SkillPassport::class)->verified($user->id);
        $sections = array_column($verified, 'items', 'key');

        $this->assertSame('ایمنی کار در ارتفاع', $sections['courses'][0]->title);
        $this->assertSame(80, $sections['exam_prep'][0]->score);
        $this->assertSame([2, 1], array_map(static fn ($item) => $item->count, $sections['reports']));
        $this->assertSame(['formula:noise-dose'], $sections['reports'][0]->tags);
        $this->assertSame(1, $sections['expert'][0]->count);

        $skills = array_map(static fn (TermData $term): string => $term->slug, $this->app->make(SkillPassport::class)->verifiedSkills($user->id));
        sort($skills);
        $this->assertSame(['heat-stress', 'noise-measurement', 'ventilation'], $skills);
    }

    public function test_the_exam_threshold_comes_from_the_panel(): void
    {
        $user = User::factory()->create();
        $pack = $this->pack();
        $this->examAttempt($user, $pack->id, correct: 3, total: 5);
        $this->examAttempt($user, $pack->id, correct: 5, total: 5, mode: AttemptMode::Practice);

        $this->assertSame([], $this->app->make(SkillPassport::class)->verified($user->id));

        $this->actingAs($this->admin(AdminRole::Finance));
        Livewire::test(JobPricingPage::class)->set('values.exam_min', '60')->call('save')->assertSet('error', null);

        $sections = $this->app->make(SkillPassport::class)->verified($user->id);
        $this->assertSame(60, $sections[0]['items'][0]->score);
    }

    public function test_declared_entries_and_skills_stay_labelled_and_the_share_page_is_opt_in_and_noindex(): void
    {
        $user = User::factory()->create(['name' => 'مریم احمدی']);
        $noise = $this->skill('noise-measurement');

        $this->actingAs($user)->get(route('jobs.passport.edit'))->assertOk()
            ->assertSee('data-page-help="passport"', false)
            ->assertSee('هنوز چیزی ثبت نشده')
            ->assertSee('این گذرنامه گواهی یا مدرک رسمی نیست');

        $this->actingAs($user)->put(route('jobs.passport.update'), [
            'headline' => 'کارشناس بهداشت حرفه‌ای',
            'province' => 'isfahan',
            'city' => 'isfahan',
            'experience_years' => 4,
            'skills' => [$noise->id],
        ])->assertRedirect(route('jobs.passport.edit'));
        $this->actingAs($user)->post(route('jobs.passport.entries.store'), [
            'kind' => 'education', 'title' => 'کارشناسی مهندسی بهداشت حرفه‌ای', 'organization' => 'دانشگاه علوم پزشکی اصفهان', 'start_year' => '1395', 'end_year' => '1399',
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('jobs.passport.entries.store'), ['kind' => 'work', 'title' => 'کارشناس HSE', 'start_year' => 1402, 'end_year' => 1400])
            ->assertSessionHasErrors('entry');

        $passport = Passport::query()->sole();
        $this->assertSame([$noise->id], $this->app->make(SkillPassport::class)->skillIds($user->id));

        // پیش‌فرض خاموش: نشانی ثابت باز نمی‌شود.
        auth()->logout();
        $this->get(route('jobs.passport.show', $passport->share_token))->assertNotFound();

        $this->actingAs($user)->post(route('jobs.passport.share'), ['shared' => '1'])->assertRedirect();
        auth()->logout();
        $this->get(route('jobs.passport.show', $passport->share_token))->assertOk()
            ->assertSee('مریم احمدی')
            ->assertSee('به اظهار خود کاربر')
            ->assertSee('کارشناسی مهندسی بهداشت حرفه‌ای')
            ->assertSee('<meta name="robots" content="noindex', false)
            ->assertDontSee($user->mobile);

        $entry = $passport->entries()->sole();
        $this->actingAs(User::factory()->create())->delete(route('jobs.passport.entries.destroy', $entry->id))->assertNotFound();
        $this->actingAs($user)->delete(route('jobs.passport.entries.destroy', $entry->id))->assertRedirect();
        $this->assertSame(0, $passport->entries()->count());

        $this->actingAs($user)->post(route('jobs.passport.share'), []);
        auth()->logout();
        $this->get(route('jobs.passport.show', $passport->share_token))->assertNotFound();
    }

    private function completedCourse(User $user, string $title): void
    {
        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => Str::slug(Str::random(8)),
            'title' => $title,
            'price_toman' => 0,
            'status' => CourseStatus::Published,
        ]);

        Enrollment::query()->create([
            'uuid' => (string) Str::uuid7(),
            'course_id' => $course->id,
            'student_user_id' => $user->id,
            'status' => EnrollmentStatus::Paid,
            'price_toman' => 0,
            'commission_rate_bp' => 0,
            'commission_toman' => 0,
            'instructor_amount_toman' => 0,
            'paid_at' => now(),
        ])->forceFill(['completed_at' => now()])->save();
    }

    /** @param  list<string>  $formulas */
    private function issuedReport(User $user, array $formulas, ReportStatus $status = ReportStatus::Issued): void
    {
        Report::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $user->id,
            'status' => $status,
            'source_key' => 'calculations',
            'source_references' => [],
        ])->forceFill([
            'tracking_code' => strtoupper(Str::random(10)),
            'issued_at' => now(),
            'snapshot' => [
                'title' => 'گزارش',
                'authorName' => 'نویسنده',
                'data' => ['sourceTitle' => 'محاسبه', 'measurements' => array_map(
                    static fn (string $formula): array => ['group' => 'سالن', 'point' => 'ایستگاه', 'value' => '1', 'unit' => null, 'formula' => $formula],
                    $formulas,
                )],
            ],
        ])->save();
    }

    private function publishedAnswer(User $user, QuestionTopic $topic): void
    {
        $question = ExpertQuestion::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory()->create()->id,
            'topic' => $topic,
            'visibility' => QuestionVisibility::Public,
            'title' => 'تهویه موضعی سالن رنگ',
            'body' => 'سرعت ربایش مناسب برای کابین رنگ چقدر است؟',
            'status' => ReviewStatus::Published,
            'published_at' => now(),
        ]);

        ExpertAnswer::query()->create([
            'uuid' => (string) Str::uuid7(),
            'question_id' => $question->id,
            'user_id' => $user->id,
            'body' => 'بسته به نوع کابین، معمولاً ۰٫۵ تا ۱ متر بر ثانیه.',
            'status' => ReviewStatus::Published,
            'published_at' => now(),
        ]);
    }

    private function examAttempt(User $user, int $packId, int $correct, int $total, AttemptMode $mode = AttemptMode::Exam): void
    {
        PrepAttempt::query()->create([
            'uuid' => (string) Str::uuid7(),
            'exam_pack_id' => $packId,
            'user_id' => $user->id,
            'mode' => $mode,
            'question_ids' => range(1, $total),
            'submitted_at' => now(),
            'late' => false,
            'correct_count' => $correct,
        ]);
    }
}
