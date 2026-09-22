<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Models\User;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\CourseReview;
use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Modules\Courses\Domain\Exam;
use App\Modules\Courses\Domain\ExamAttempt;
use App\Modules\Courses\Domain\ExamChoice;
use App\Modules\Courses\Domain\ExamQuestion;
use App\Modules\Courses\Domain\SessionProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class ModelsSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_full_course_graph_can_be_built_and_related(): void
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => $instructor->id,
            'slug' => 'safety-101',
            'title' => 'ایمنی پایه',
            'price_toman' => 200_000,
            'status' => CourseStatus::Published,
        ]);

        $session = CourseSession::query()->create([
            'course_id' => $course->id,
            'title' => 'جلسه اول',
            'content_type' => 'text',
            'content' => 'متن جلسه',
            'position' => 1,
        ]);

        $exam = Exam::query()->create(['course_id' => $course->id, 'pass_percentage' => 60]);
        $question = ExamQuestion::query()->create(['exam_id' => $exam->id, 'text' => 'سؤال ۱', 'position' => 1]);
        ExamChoice::query()->create(['exam_question_id' => $question->id, 'text' => 'درست', 'is_correct' => true]);
        ExamChoice::query()->create(['exam_question_id' => $question->id, 'text' => 'غلط', 'is_correct' => false]);

        $enrollment = Enrollment::query()->create([
            'uuid' => (string) Str::uuid7(),
            'course_id' => $course->id,
            'student_user_id' => $student->id,
            'status' => EnrollmentStatus::Paid,
            'price_toman' => 200_000,
            'commission_rate_bp' => 2000,
            'commission_toman' => 40_000,
            'instructor_amount_toman' => 160_000,
            'paid_at' => now(),
        ]);

        SessionProgress::query()->create([
            'enrollment_id' => $enrollment->id,
            'course_session_id' => $session->id,
            'completed_at' => now(),
        ]);

        ExamAttempt::query()->create([
            'enrollment_id' => $enrollment->id,
            'exam_id' => $exam->id,
            'score_percentage' => 100,
            'passed' => true,
        ]);

        CourseReview::query()->create([
            'enrollment_id' => $enrollment->id,
            'rating' => 5,
            'comment' => 'عالی بود',
        ]);

        $this->assertTrue($enrollment->refresh()->hasCompletedSession($session->id));
        $this->assertTrue($enrollment->hasPassedExam());
        $this->assertSame(100, $enrollment->bestExamScore());
        $this->assertNotNull($course->exam);
        $this->assertCount(1, $course->refresh()->sessions);
        $this->assertNotNull($enrollment->review);
    }

    public function test_an_exam_attempt_is_immutable(): void
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => $instructor->id,
            'slug' => 'safety-102',
            'title' => 'دوره دیگر',
            'price_toman' => 1000,
            'status' => CourseStatus::Draft,
        ]);

        $exam = Exam::query()->create(['course_id' => $course->id]);

        $enrollment = Enrollment::query()->create([
            'uuid' => (string) Str::uuid7(),
            'course_id' => $course->id,
            'student_user_id' => $student->id,
            'status' => EnrollmentStatus::Paid,
            'price_toman' => 1000,
            'commission_rate_bp' => 2000,
            'commission_toman' => 200,
            'instructor_amount_toman' => 800,
        ]);

        $attempt = ExamAttempt::query()->create([
            'enrollment_id' => $enrollment->id,
            'exam_id' => $exam->id,
            'score_percentage' => 40,
            'passed' => false,
        ]);

        $this->expectException(RuntimeException::class);
        $attempt->update(['passed' => true]);
    }
}
