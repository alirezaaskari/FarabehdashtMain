<?php

declare(strict_types=1);

namespace App\Modules\Courses\Filament\Pages;

use App\Modules\Courses\Actions\PublishCourse;
use App\Modules\Courses\Actions\RejectCourse;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * بررسی دوره‌های در انتظار انتشار — همان الگوی `ProductReviewPage` ماژول
 * تجارت و `ContentHealthPage` دانشنامه.
 */
final class CourseReviewPage extends Page
{
    public const ABILITY = 'admin.content.review';

    protected static ?string $slug = 'courses-review';

    protected static ?int $navigationSort = 50;

    protected string $view = 'courses::filament.pages.review';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<int, string> */
    public array $rejectNotes = [];

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
                'price' => $course->price()->format(),
                'sessions' => $course->sessions->count(),
            ])
            ->all();
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
