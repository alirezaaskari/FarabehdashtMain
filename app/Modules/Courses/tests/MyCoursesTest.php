<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Models\User;
use App\Modules\Courses\Actions\AddCourseSession;
use App\Modules\Courses\Actions\CompleteSession;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Modules\Courses\Services\CourseContentApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * «دوره‌های من» — ادامه یادگیری از همان جایی که مانده بود.
 */
final class MyCoursesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('courses.mine'))->assertRedirect(route('login'));
    }

    public function test_a_learner_without_courses_sees_the_way_to_the_catalogue(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('courses.mine'))
            ->assertOk()
            ->assertSee('هنوز در دوره‌ای ثبت‌نام نکرده‌اید')
            ->assertSee(route('courses.index'));
    }

    public function test_progress_counts_only_approved_sessions(): void
    {
        $student = User::factory()->create();
        $enrollment = $this->enroll($student, 'دوره ارگونومی');
        $course = $enrollment->course;

        $add = $this->app->make(AddCourseSession::class);
        $first = $add->handle($course, 'جلسه ۱', 'text', 'متن');
        $add->handle($course, 'جلسه ۲', 'text', 'متن');
        $this->app->make(CourseContentApproval::class)->approve($course);
        // جلسه در انتظار تأیید در مخرج نیست.
        $add->handle($course, 'جلسه تازه', 'text', 'متن');

        $this->app->make(CompleteSession::class)->handle($enrollment, $first->refresh());

        $this->actingAs($student)->get(route('courses.mine'))
            ->assertOk()
            ->assertSee('دوره ارگونومی')
            ->assertSee('۱ از ۲ جلسه')
            ->assertSee('aria-valuenow="50"', escape: false)
            ->assertSee('ادامه یادگیری')
            ->assertSee(route('courses.learn', $course));
    }

    public function test_unpaid_enrollments_and_other_learners_courses_are_left_out(): void
    {
        $student = User::factory()->create();
        $this->enroll($student, 'پرداخت رهاشده', EnrollmentStatus::Pending);
        $this->enroll(User::factory()->create(), 'دوره دیگری');

        $this->actingAs($student)->get(route('courses.mine'))
            ->assertOk()
            ->assertSee('هنوز در دوره‌ای ثبت‌نام نکرده‌اید')
            ->assertDontSee('پرداخت رهاشده')
            ->assertDontSee('دوره دیگری');
    }

    public function test_an_unfinished_course_comes_before_a_finished_one(): void
    {
        $student = User::factory()->create();
        $this->enroll($student, 'دوره تمام‌شده', completed: true);
        $this->enroll($student, 'دوره نیمه‌تمام');

        $this->actingAs($student)->get(route('courses.mine'))
            ->assertOk()
            ->assertSeeInOrder(['دوره نیمه‌تمام', 'دوره تمام‌شده', 'مرور دوره']);
    }

    private function enroll(User $student, string $title, EnrollmentStatus $status = EnrollmentStatus::Paid, bool $completed = false): Enrollment
    {
        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'c-'.Str::random(8),
            'title' => $title,
            'price_toman' => 100_000,
            'status' => CourseStatus::Published,
        ]);

        return Enrollment::query()->create([
            'uuid' => (string) Str::uuid7(),
            'course_id' => $course->id,
            'student_user_id' => $student->id,
            'status' => $status,
            'price_toman' => 100_000,
            'commission_rate_bp' => 2000,
            'commission_toman' => 20_000,
            'instructor_amount_toman' => 80_000,
            'paid_at' => $status === EnrollmentStatus::Paid ? now() : null,
            'completed_at' => $completed ? now() : null,
        ])->refresh();
    }
}
