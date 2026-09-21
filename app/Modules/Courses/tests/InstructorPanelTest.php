<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Models\User;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class InstructorPanelTest extends TestCase
{
    use RefreshDatabase;

    private function instructor(): User
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $profile = $this->app->make(RequestProfileActivation::class)->handle($user, ProfileType::Instructor);
        $this->app->make(ReviewProfileRequest::class)->approve($profile, $admin);

        return $user->fresh() ?? $user;
    }

    public function test_a_non_instructor_cannot_open_the_instructor_courses_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('courses.instructor.courses.index'))->assertForbidden();
    }

    public function test_an_instructor_can_create_a_course(): void
    {
        $instructor = $this->instructor();

        $this->actingAs($instructor)
            ->post(route('courses.instructor.courses.store'), [
                'title' => 'ایمنی در انبار',
                'description' => 'اصول کار ایمن در انبار.',
                'price' => '۲۰۰۰۰۰',
            ])
            ->assertRedirect();

        $course = Course::query()->where('instructor_user_id', $instructor->id)->sole();
        $this->assertSame('ایمنی در انبار', $course->title);
        $this->assertSame(200_000, $course->price_toman);
        $this->assertSame(CourseStatus::Draft, $course->status);
    }

    public function test_an_instructor_can_add_a_session_and_an_exam_question_then_submit_for_review(): void
    {
        $instructor = $this->instructor();
        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => $instructor->id,
            'slug' => 'c-'.Str::random(8),
            'title' => 'دوره من',
            'price_toman' => 100_000,
            'status' => CourseStatus::Draft,
        ]);

        $this->actingAs($instructor)
            ->post(route('courses.instructor.courses.sessions', $course), [
                'title' => 'جلسه اول',
                'content_type' => 'text',
                'content' => 'متن جلسه',
            ])
            ->assertRedirect();

        $this->assertCount(1, $course->refresh()->sessions);

        $this->actingAs($instructor)
            ->post(route('courses.instructor.courses.questions', $course), [
                'text' => 'کدام گزینه درست است؟',
                'choices' => ['گزینه یک', 'گزینه دو', '', ''],
                'correct' => 0,
            ])
            ->assertRedirect();

        $this->assertNotNull($course->refresh()->exam);
        $this->assertCount(1, $course->exam->questions);
        $this->assertCount(2, $course->exam->questions->first()->choices);

        $this->actingAs($instructor)
            ->post(route('courses.instructor.courses.submit', $course))
            ->assertRedirect();

        $this->assertSame(CourseStatus::InReview, $course->refresh()->status);
    }

    public function test_an_instructor_cannot_edit_another_instructors_course(): void
    {
        $instructor = $this->instructor();
        $other = User::factory()->create();

        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => $other->id,
            'slug' => 'not-mine',
            'title' => 'دوره دیگری',
            'price_toman' => 10_000,
            'status' => CourseStatus::Draft,
        ]);

        $this->actingAs($instructor)->get(route('courses.instructor.courses.edit', $course))->assertForbidden();
    }

    public function test_the_sales_page_shows_for_an_instructor_with_no_sales(): void
    {
        $instructor = $this->instructor();

        $this->actingAs($instructor)->get(route('courses.instructor.sales'))->assertOk();
    }
}
