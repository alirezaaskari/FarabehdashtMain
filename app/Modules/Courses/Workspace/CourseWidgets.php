<?php

declare(strict_types=1);

namespace App\Modules\Courses\Workspace;

use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Support\PersianDigits;
use App\Support\Workspace\WidgetRow;
use App\Support\Workspace\WidgetStat;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;
use Illuminate\Support\Facades\Route;

/**
 * دو کارت: «در حال یادگیری» روی نمای شخصی و «دوره‌های من» روی نمای مدرس.
 */
final readonly class CourseWidgets implements WorkspaceWidgetSource
{
    private const LIMIT = 3;

    /** مقدار نوع پروفایل مدرس — رشته، چون Enum ماژول هویت import نمی‌شود. */
    private const INSTRUCTOR = 'instructor';

    public function widgets(User $user, WorkspaceView $view): array
    {
        return match (true) {
            $view->isPersonal() => $this->learning($user),
            $view->is(self::INSTRUCTOR) => $this->teaching($user),
            default => [],
        };
    }

    /** @return list<WorkspaceWidget> */
    private function learning(User $user): array
    {
        if (! Route::has('courses.learn') || ! Route::has('courses.index')) {
            return [];
        }

        $enrollments = Enrollment::query()
            ->forStudent((int) $user->getKey())
            ->where('status', EnrollmentStatus::Paid->value)
            ->with('course')
            ->latest('paid_at')
            ->limit(self::LIMIT)
            ->get();

        return [new WorkspaceWidget(
            key: 'learning',
            title: 'در حال یادگیری',
            order: 25,
            rows: $enrollments
                ->map(static fn (Enrollment $enrollment): WidgetRow => new WidgetRow(
                    label: $enrollment->course->title,
                    url: route('courses.learn', $enrollment->course),
                    meta: $enrollment->completed_at === null ? 'در جریان' : 'تمام‌شده',
                ))
                ->values()
                ->all(),
            empty: 'هنوز در دوره‌ای ثبت‌نام نکرده‌اید.',
            actionUrl: route('courses.index'),
            actionLabel: 'دیدن دوره‌ها',
        )];
    }

    /** @return list<WorkspaceWidget> */
    private function teaching(User $user): array
    {
        if (! Route::has('courses.instructor.courses.index')) {
            return [];
        }

        $query = Course::query()->ownedBy((int) $user->getKey());

        return [new WorkspaceWidget(
            key: 'teaching',
            title: 'دوره‌های من',
            order: 10,
            stats: [
                new WidgetStat('منتشرشده', PersianDigits::from((clone $query)->where('status', CourseStatus::Published->value)->count())),
                new WidgetStat('در صف بررسی', PersianDigits::from((clone $query)->where('status', CourseStatus::InReview->value)->count())),
            ],
            rows: $query->latest('updated_at')->limit(self::LIMIT)->get()
                ->map(static fn (Course $course): WidgetRow => new WidgetRow(
                    label: $course->title,
                    url: Route::has('courses.instructor.courses.edit') ? route('courses.instructor.courses.edit', $course) : null,
                    meta: $course->status->label(),
                ))
                ->values()
                ->all(),
            empty: 'نخستین دوره‌تان را بسازید؛ پس از تأیید مدیر منتشر می‌شود.',
            actionUrl: route('courses.instructor.courses.index'),
            actionLabel: 'مدیریت دوره‌ها',
        )];
    }
}
