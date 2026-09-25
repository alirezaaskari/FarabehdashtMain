<?php

declare(strict_types=1);

namespace App\Modules\Courses\Filament\Pages;

use App\Modules\Courses\Actions\ApproveCourseChanges;
use App\Modules\Courses\Actions\PublishCourse;
use App\Modules\Courses\Actions\RejectCourse;
use App\Modules\Courses\Actions\RejectCourseChanges;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Services\CourseContentApproval;
use App\Support\Admin\NavigationGroup;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;
use UnitEnum;

/**
 * بررسی دوره‌های در انتظار انتشار — همان الگوی `ProductReviewPage` ماژول
 * تجارت و `ContentHealthPage` دانشنامه.
 *
 * بخش دوم: جلسه و سؤال‌هایی که مدرس به دوره منتشرشده افزوده. دوره منتشر
 * می‌ماند؛ فقط افزوده‌ها تا تأیید از دانشجو پنهان‌اند.
 */
final class CourseReviewPage extends Page
{
    public const ABILITY = 'admin.content.review';

    protected static ?string $slug = 'courses-review';

    protected static ?int $navigationSort = 50;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Review;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected string $view = 'courses::filament.pages.review';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<int, string> */
    public array $rejectNotes = [];

    /** @var list<array<string, mixed>> دوره‌های منتشرشده با جلسه یا سؤال تازه */
    public array $changes = [];

    /** @var array<int, string> */
    public array $changeNotes = [];

    public static function getNavigationLabel(): string
    {
        return 'بررسی دوره‌ها';
    }

    public function getTitle(): string
    {
        return 'بررسی دوره‌های آموزشی';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function publish(int $id, PublishCourse $publish): void
    {
        $course = Course::query()->find($id);

        if ($course === null) {
            return;
        }

        try {
            $publish->handle($course, $this->actorId());

            Notification::make()->title('منتشر شد: '.$course->title)->success()->send();
        } catch (RuntimeException $exception) {
            Notification::make()->title('منتشر نشد')->body($exception->getMessage())->danger()->send();
        }

        $this->load();
    }

    public function reject(int $id, RejectCourse $reject): void
    {
        $course = Course::query()->find($id);
        $note = trim($this->rejectNotes[$id] ?? '');

        if ($course === null || $this->actorId() === null) {
            return;
        }

        try {
            $reject->handle($course, $this->actorId(), $note);

            Notification::make()->title('رد شد: '.$course->title)->success()->send();
        } catch (RuntimeException $exception) {
            Notification::make()->title('رد نشد')->body($exception->getMessage())->danger()->send();
        }

        unset($this->rejectNotes[$id]);
        $this->load();
    }

    public function approveChanges(int $id, ApproveCourseChanges $approve): void
    {
        $course = Course::query()->find($id);

        if ($course === null || $this->actorId() === null) {
            return;
        }

        try {
            $approve->handle($course, $this->actorId());

            Notification::make()->title('تغییرات تأیید شد: '.$course->title)->success()->send();
        } catch (RuntimeException $exception) {
            Notification::make()->title('تأیید نشد')->body($exception->getMessage())->danger()->send();
        }

        $this->load();
    }

    public function rejectChanges(int $id, RejectCourseChanges $reject): void
    {
        $course = Course::query()->find($id);
        $note = trim($this->changeNotes[$id] ?? '');

        if ($course === null || $this->actorId() === null) {
            return;
        }

        try {
            $reject->handle($course, $this->actorId(), $note);

            Notification::make()->title('تغییرات رد شد: '.$course->title)->success()->send();
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Notification::make()->title('رد نشد')->body($exception->getMessage())->danger()->send();
        }

        unset($this->changeNotes[$id]);
        $this->load();
    }

    private function load(): void
    {
        $this->rows = Course::query()
            ->where('status', CourseStatus::InReview->value)
            ->with(['instructor', 'sessions'])
            ->oldest('updated_at')
            ->get()
            ->map(static fn (Course $course): array => [
                'id' => $course->id,
                'title' => $course->title,
                'instructor' => $course->instructor->name ?? ('کاربر #'.$course->instructor_user_id),
                'price' => $course->priceLabel(),
                'sessions' => $course->sessions->count(),
            ])
            ->all();

        $approval = app(CourseContentApproval::class);

        $this->changes = Course::query()
            ->withPendingChanges()
            ->with(['instructor'])
            ->oldest('updated_at')
            ->get()
            ->map(static function (Course $course) use ($approval): array {
                $pending = $approval->pendingCounts($course);

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'instructor' => $course->instructor->name ?? ('کاربر #'.$course->instructor_user_id),
                    'sessions' => CourseSession::query()->where('course_id', $course->id)->pendingApproval()->orderBy('position')->pluck('title')->all(),
                    'questions' => $pending['questions'],
                ];
            })
            ->all();
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
