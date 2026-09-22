<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Models\User;
use App\Modules\Core\Domain\AuditLog;
use App\Modules\Courses\Actions\AddCourseSession;
use App\Modules\Courses\Actions\AddExamQuestion;
use App\Modules\Courses\Actions\PublishCourse;
use App\Modules\Courses\Actions\RejectCourse;
use App\Modules\Courses\Actions\RetireCourse;
use App\Modules\Courses\Actions\SubmitCourseForReview;
use App\Modules\Courses\Admin\PendingCourses;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

final class CourseLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function draftCourse(int $priceToman = 200_000): Course
    {
        return Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'c-'.Str::random(8),
            'title' => 'دوره آزمایشی',
            'price_toman' => $priceToman,
            'status' => CourseStatus::Draft,
        ])->refresh();
    }

    private function courseWithSession(Course $course): Course
    {
        $this->app->make(AddCourseSession::class)->handle($course, 'جلسه اول', 'text', 'محتوا');

        return $course->refresh();
    }

    public function test_a_course_without_a_session_cannot_be_submitted(): void
    {
        $this->expectExceptionMessageMatches('/هیچ جلسه‌ای/u');

        $this->app->make(SubmitCourseForReview::class)->handle($this->draftCourse());
    }

    public function test_a_fully_ready_course_moves_to_in_review(): void
    {
        $course = $this->courseWithSession($this->draftCourse());

        $submitted = $this->app->make(SubmitCourseForReview::class)->handle($course);

        $this->assertSame(CourseStatus::InReview, $submitted->status);
        $this->assertContains('course', iterator_to_array($this->pendingKinds()));
    }

    public function test_publishing_writes_an_audit_row_and_leaves_the_queue(): void
    {
        $admin = User::factory()->create();
        $course = $this->app->make(SubmitCourseForReview::class)->handle($this->courseWithSession($this->draftCourse()));

        $published = $this->app->make(PublishCourse::class)->handle($course, $admin->id);

        $this->assertSame(CourseStatus::Published, $published->status);
        $this->assertNotContains('course', iterator_to_array($this->pendingKinds()));

        $row = AuditLog::query()->where('action', 'courses.course_published')->sole();
        $this->assertSame($published->uuid, $row->subject_id);
    }

    public function test_rejecting_requires_a_note(): void
    {
        $admin = User::factory()->create();
        $course = $this->app->make(SubmitCourseForReview::class)->handle($this->courseWithSession($this->draftCourse()));

        $this->expectExceptionMessageMatches('/یادداشت/u');
        $this->app->make(RejectCourse::class)->handle($course, $admin->id, '');
    }

    public function test_a_rejected_course_can_be_resubmitted(): void
    {
        $admin = User::factory()->create();
        $course = $this->app->make(SubmitCourseForReview::class)->handle($this->courseWithSession($this->draftCourse()));

        $rejected = $this->app->make(RejectCourse::class)->handle($course, $admin->id, 'محتوا ناقص است.');
        $this->assertSame(CourseStatus::Rejected, $rejected->status);

        $resubmitted = $this->app->make(SubmitCourseForReview::class)->handle($rejected);
        $this->assertSame(CourseStatus::InReview, $resubmitted->status);
    }

    public function test_retiring_a_published_course_stops_new_enrollment(): void
    {
        $admin = User::factory()->create();
        $course = $this->app->make(PublishCourse::class)->handle(
            $this->app->make(SubmitCourseForReview::class)->handle($this->courseWithSession($this->draftCourse())),
            $admin->id,
        );

        $retired = $this->app->make(RetireCourse::class)->handle($course);

        $this->assertSame(CourseStatus::Retired, $retired->status);
        $this->assertFalse($retired->status->enrollable());
    }

    public function test_sessions_are_ordered_by_addition(): void
    {
        $course = $this->draftCourse();
        $add = $this->app->make(AddCourseSession::class);

        $add->handle($course, 'اول', 'text', null);
        $add->handle($course, 'دوم', 'text', null);
        $add->handle($course, 'سوم', 'text', null);

        $titles = $course->refresh()->sessions->pluck('title')->all();
        $this->assertSame(['اول', 'دوم', 'سوم'], $titles);
    }

    public function test_adding_an_exam_question_creates_the_exam_on_first_use(): void
    {
        $course = $this->draftCourse();

        $this->assertNull($course->exam);

        $question = $this->app->make(AddExamQuestion::class)->handle($course, 'پرسش ۱', [
            ['text' => 'درست', 'is_correct' => true],
            ['text' => 'غلط', 'is_correct' => false],
        ]);

        $this->assertNotNull($course->refresh()->exam);
        $this->assertCount(2, $question->choices);
    }

    public function test_a_question_without_a_correct_choice_is_rejected(): void
    {
        $course = $this->draftCourse();

        $this->expectException(InvalidArgumentException::class);

        $this->app->make(AddExamQuestion::class)->handle($course, 'پرسش ۱', [
            ['text' => 'الف', 'is_correct' => false],
            ['text' => 'ب', 'is_correct' => false],
        ]);
    }

    /** @return iterable<string> */
    private function pendingKinds(): iterable
    {
        foreach ($this->app->make(PendingCourses::class)->pendingItems() as $item) {
            yield $item->kind;
        }
    }
}
