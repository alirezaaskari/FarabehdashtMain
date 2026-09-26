<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Models\User;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** جابه‌جایی جلسه با «بالا / پایین» (بخش ۱۸-۱۱). */
final class SessionOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_instructor_moves_a_session_up_and_down(): void
    {
        [$instructor, $course, $sessions] = $this->course(CourseStatus::Published);

        $this->actingAs($instructor)
            ->post(route('courses.instructor.courses.sessions.move', [$course, $sessions[2]]), ['direction' => 'up'])
            ->assertRedirect(route('courses.instructor.courses.edit', $course).'#sessions');

        $this->assertSame(['یک', 'سه', 'دو'], $this->titles($course));

        $this->actingAs($instructor)
            ->post(route('courses.instructor.courses.sessions.move', [$course, $sessions[0]]), ['direction' => 'down']);

        $this->assertSame(['سه', 'یک', 'دو'], $this->titles($course));
    }

    public function test_moving_past_either_end_changes_nothing(): void
    {
        [$instructor, $course, $sessions] = $this->course(CourseStatus::Draft);

        $this->actingAs($instructor)
            ->post(route('courses.instructor.courses.sessions.move', [$course, $sessions[0]]), ['direction' => 'up']);
        $this->actingAs($instructor)
            ->post(route('courses.instructor.courses.sessions.move', [$course, $sessions[2]]), ['direction' => 'down']);

        $this->assertSame(['یک', 'دو', 'سه'], $this->titles($course));
    }

    public function test_duplicate_positions_are_renumbered_before_moving(): void
    {
        [$instructor, $course, $sessions] = $this->course(CourseStatus::Draft);
        CourseSession::query()->whereKey($sessions[1]->id)->update(['position' => 1]);

        $this->actingAs($instructor)
            ->post(route('courses.instructor.courses.sessions.move', [$course, $sessions[2]]), ['direction' => 'up']);

        $this->assertSame(['یک', 'سه', 'دو'], $this->titles($course));
        $this->assertSame([1, 2, 3], $course->sessions()->pluck('position')->all());
    }

    public function test_another_instructor_or_another_courses_session_is_refused(): void
    {
        [$instructor, $course, $sessions] = $this->course(CourseStatus::Draft);
        [$other, $otherCourse] = $this->course(CourseStatus::Draft);

        $this->actingAs($other)
            ->post(route('courses.instructor.courses.sessions.move', [$course, $sessions[1]]), ['direction' => 'up'])
            ->assertForbidden();

        $this->actingAs($other)
            ->post(route('courses.instructor.courses.sessions.move', [$otherCourse, $sessions[1]]), ['direction' => 'up'])
            ->assertNotFound();

        $this->assertSame(['یک', 'دو', 'سه'], $this->titles($course));
    }

    public function test_a_retired_course_keeps_its_order_and_hides_the_buttons(): void
    {
        [$instructor, $course, $sessions] = $this->course(CourseStatus::Retired);

        $this->actingAs($instructor)
            ->post(route('courses.instructor.courses.sessions.move', [$course, $sessions[1]]), ['direction' => 'up'])
            ->assertSessionHasErrors('session');

        $this->actingAs($instructor)
            ->get(route('courses.instructor.courses.edit', $course))
            ->assertOk()
            ->assertDontSee('یک جلسه بالاتر');
    }

    public function test_the_edit_page_shows_the_move_buttons(): void
    {
        [$instructor, $course] = $this->course(CourseStatus::Draft);

        $this->actingAs($instructor)
            ->get(route('courses.instructor.courses.edit', $course))
            ->assertOk()
            ->assertSee('بردن «دو» یک جلسه بالاتر', false);
    }

    /** @return array{User, Course, list<CourseSession>} */
    private function course(CourseStatus $status): array
    {
        $instructor = User::factory()->create();
        $profile = $this->app->make(RequestProfileActivation::class)->handle($instructor, ProfileType::Instructor);
        $this->app->make(ReviewProfileRequest::class)->approve($profile, User::factory()->create());

        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => $instructor->id,
            'slug' => 'c-'.Str::lower(Str::random(8)),
            'title' => 'دوره',
            'price_toman' => 0,
            'status' => $status,
        ]);

        $sessions = [];

        foreach (['یک', 'دو', 'سه'] as $index => $title) {
            $sessions[] = CourseSession::query()->create([
                'course_id' => $course->id,
                'title' => $title,
                'content_type' => 'text',
                'content' => 'متن',
                'position' => $index + 1,
            ]);
        }

        return [$instructor->fresh() ?? $instructor, $course, $sessions];
    }

    /** @return list<string> */
    private function titles(Course $course): array
    {
        return $course->sessions()->orderBy('id')->pluck('title')->all();
    }
}
