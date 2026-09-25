<?php

declare(strict_types=1);

namespace App\Modules\Courses\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Courses\Actions\AddCourseSession;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * دسترسی صفحه مدیریت بررسی دوره — منطق واقعی انتشار/رد در CourseLifecycleTest
 * آزموده شده؛ این‌جا فقط دروازه دسترسی و بالاآمدن صفحه سنجیده می‌شود.
 */
final class AdminCoursePagesTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    public function test_a_content_admin_can_open_the_course_review_page(): void
    {
        $admin = $this->adminWith(AdminRole::Content);

        Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'c-'.Str::random(8),
            'title' => 'دوره در انتظار',
            'price_toman' => 10_000,
            'status' => CourseStatus::InReview,
        ]);

        $this->actingAs($admin)
            ->get('/'.config('admin.path').'/courses-review')
            ->assertOk()
            ->assertSee('دوره در انتظار');
    }

    public function test_the_review_page_lists_additions_to_live_courses(): void
    {
        $admin = $this->adminWith(AdminRole::Content);

        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'c-'.Str::random(8),
            'title' => 'دوره منتشرشده',
            'price_toman' => 10_000,
            'status' => CourseStatus::Published,
        ]);
        $this->app->make(AddCourseSession::class)->handle($course, 'جلسه افزوده', 'text', 'متن');

        $this->actingAs($admin)
            ->get('/'.config('admin.path').'/courses-review')
            ->assertOk()
            ->assertSee('افزوده‌های تازه به دوره‌های منتشرشده')
            ->assertSee('جلسه افزوده');
    }

    public function test_a_finance_admin_cannot_open_the_course_review_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get('/'.config('admin.path').'/courses-review')
            ->assertForbidden();
    }
}
