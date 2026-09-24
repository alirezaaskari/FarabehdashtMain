<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Models\User;
use App\Modules\Courses\Actions\AddCourseSession;
use App\Modules\Courses\Actions\AddExamQuestion;
use App\Modules\Courses\Actions\CompleteSession;
use App\Modules\Courses\Actions\SubmitCourseReview;
use App\Modules\Courses\Actions\SubmitExamAttempt;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Modules\Courses\Domain\ExamQuestion;
use App\Modules\Courses\Services\CourseContentApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

final class LearnEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    private function paidEnrollment(): Enrollment
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => $instructor->id,
            'slug' => 'c-'.Str::random(8),
            'title' => 'دوره ایمنی',
            'price_toman' => 100_000,
            'status' => CourseStatus::Published,
        ]);

        return Enrollment::query()->create([
            'uuid' => (string) Str::uuid7(),
            'course_id' => $course->id,
            'student_user_id' => $student->id,
            'status' => EnrollmentStatus::Paid,
            'price_toman' => 100_000,
            'commission_rate_bp' => 2000,
            'commission_toman' => 20_000,
            'instructor_amount_toman' => 80_000,
            'paid_at' => now(),
        ]);
    }

    /** جلسه‌ای که مدیر تأیید کرده و دانشجو می‌بیند. */
    private function liveSession(Course $course, string $title, string $type, string $content): CourseSession
    {
        $session = $this->app->make(AddCourseSession::class)->handle($course, $title, $type, $content);
        $this->app->make(CourseContentApproval::class)->approve($course);

        return $session->refresh();
    }

    /** @param  list<array{text: string, is_correct: bool}>  $choices */
    private function liveQuestion(Course $course, string $text, array $choices): ExamQuestion
    {
        $question = $this->app->make(AddExamQuestion::class)->handle($course, $text, $choices);
        $this->app->make(CourseContentApproval::class)->approve($course);

        return $question->refresh();
    }

    public function test_a_session_added_to_a_live_course_waits_for_approval(): void
    {
        $enrollment = $this->paidEnrollment();
        $this->liveSession($enrollment->course, 'جلسه ۱', 'text', 'متن');
        $pending = $this->app->make(AddCourseSession::class)->handle($enrollment->course, 'جلسه تازه', 'text', 'متن تازه');

        $this->actingAs($enrollment->student)
            ->get(route('courses.learn', $enrollment->course))
            ->assertOk()
            ->assertSee('جلسه ۱')
            ->assertDontSee('جلسه تازه');

        $this->expectException(InvalidArgumentException::class);
        $this->app->make(CompleteSession::class)->handle($enrollment, $pending);
    }

    public function test_completing_the_only_session_of_a_course_without_an_exam_marks_it_complete(): void
    {
        $enrollment = $this->paidEnrollment();
        $session = $this->liveSession($enrollment->course, 'جلسه ۱', 'text', 'متن');

        $this->app->make(CompleteSession::class)->handle($enrollment, $session);

        $this->assertNotNull($enrollment->refresh()->completed_at);
    }

    public function test_completing_a_session_twice_is_a_no_op(): void
    {
        $enrollment = $this->paidEnrollment();
        $session = $this->liveSession($enrollment->course, 'جلسه ۱', 'text', 'متن');

        $first = $this->app->make(CompleteSession::class)->handle($enrollment, $session);
        $second = $this->app->make(CompleteSession::class)->handle($enrollment, $session);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $enrollment->progress()->count());
    }

    public function test_a_course_with_an_exam_is_not_complete_until_the_exam_is_passed(): void
    {
        $enrollment = $this->paidEnrollment();
        $course = $enrollment->course;

        $session = $this->liveSession($course, 'جلسه ۱', 'text', 'متن');

        $question = $this->liveQuestion($course, 'پرسش ۱', [
            ['text' => 'درست', 'is_correct' => true],
            ['text' => 'غلط', 'is_correct' => false],
        ]);

        $this->app->make(CompleteSession::class)->handle($enrollment, $session);
        $this->assertNull($enrollment->refresh()->completed_at, 'دوره با آزمون نباید بدون شرکت در آزمون تکمیل شود.');

        $wrongChoice = $question->choices->firstWhere('is_correct', false);
        $this->app->make(SubmitExamAttempt::class)->handle($enrollment, $course->exam, [$question->id => $wrongChoice->id]);
        $this->assertNull($enrollment->refresh()->completed_at, 'قبول نشدن در آزمون نباید دوره را تکمیل کند.');

        $correctChoice = $question->choices->firstWhere('is_correct', true);
        $this->app->make(SubmitExamAttempt::class)->handle($enrollment, $course->exam, [$question->id => $correctChoice->id]);
        $this->assertNotNull($enrollment->refresh()->completed_at);
    }

    public function test_a_review_cannot_be_submitted_before_completion(): void
    {
        $enrollment = $this->paidEnrollment();

        $this->expectException(InvalidArgumentException::class);
        $this->app->make(SubmitCourseReview::class)->handle($enrollment, 5, 'عالی');
    }

    public function test_a_review_can_be_submitted_once_after_completion(): void
    {
        $enrollment = $this->paidEnrollment();
        $session = $this->liveSession($enrollment->course, 'جلسه ۱', 'text', 'متن');
        $this->app->make(CompleteSession::class)->handle($enrollment, $session);

        $review = $this->app->make(SubmitCourseReview::class)->handle($enrollment->refresh(), 5, 'عالی بود');
        $this->assertSame(5, $review->rating);

        $this->expectException(InvalidArgumentException::class);
        $this->app->make(SubmitCourseReview::class)->handle($enrollment->refresh(), 4, null);
    }

    public function test_an_unpaid_enrollment_cannot_complete_a_session(): void
    {
        $enrollment = $this->paidEnrollment();
        $enrollment->forceFill(['status' => EnrollmentStatus::Pending])->save();
        $session = $this->liveSession($enrollment->course, 'جلسه ۱', 'text', 'متن');

        $this->expectException(InvalidArgumentException::class);
        $this->app->make(CompleteSession::class)->handle($enrollment->refresh(), $session);
    }

    public function test_the_learning_page_renders_for_an_enrolled_student(): void
    {
        $enrollment = $this->paidEnrollment();
        $this->liveSession($enrollment->course, 'جلسه ۱', 'text', 'متن جلسه');

        $this->actingAs($enrollment->student)
            ->get(route('courses.learn', $enrollment->course))
            ->assertOk()
            ->assertSee('جلسه ۱');
    }

    public function test_a_stranger_cannot_open_the_learning_page(): void
    {
        $enrollment = $this->paidEnrollment();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('courses.learn', $enrollment->course))
            ->assertForbidden();
    }
}
