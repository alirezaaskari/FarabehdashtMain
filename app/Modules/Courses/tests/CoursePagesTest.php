<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Models\User;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CoursePagesTest extends TestCase
{
    use RefreshDatabase;

    private function published(): Course
    {
        return Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'safety-101',
            'title' => 'ایمنی پایه',
            'description' => 'آشنایی با اصول ایمنی محیط کار.',
            'price_toman' => 150_000,
            'status' => CourseStatus::Published,
        ])->refresh();
    }

    public function test_the_catalog_lists_published_courses(): void
    {
        $course = $this->published();

        $this->get(route('courses.index'))->assertOk()->assertSee($course->title);
    }

    public function test_a_draft_course_is_not_listed(): void
    {
        Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'hidden-draft',
            'title' => 'پیش‌نویس نادیده',
            'price_toman' => 10_000,
            'status' => CourseStatus::Draft,
        ]);

        $this->get(route('courses.index'))->assertDontSee('پیش‌نویس نادیده');
    }

    public function test_the_course_page_renders(): void
    {
        $course = $this->published();

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee($course->title)
            ->assertSee($course->price()->format());
    }

    public function test_a_draft_courses_page_is_not_found(): void
    {
        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'hidden-draft-2',
            'title' => 'پیش‌نویس دیگر',
            'price_toman' => 10_000,
            'status' => CourseStatus::Draft,
        ]);

        $this->get(route('courses.show', $course->slug))->assertNotFound();
    }
}
