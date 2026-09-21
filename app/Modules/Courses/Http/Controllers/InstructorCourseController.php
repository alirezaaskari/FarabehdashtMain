<?php

declare(strict_types=1);

namespace App\Modules\Courses\Http\Controllers;

use App\Modules\Courses\Actions\AddCourseSession;
use App\Modules\Courses\Actions\AddExamQuestion;
use App\Modules\Courses\Actions\RetireCourse;
use App\Modules\Courses\Actions\SubmitCourseForReview;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * مدیریت دوره توسط مدرس — پیش از یکپارچگی با میزکار مشترک (بخش ۱۵)، مثل
 * پنل فروشنده ماژول تجارت، روی پوسته عمومی است.
 */
final readonly class InstructorCourseController
{
    public function __construct(
        private SubmitCourseForReview $submit,
        private AddCourseSession $addSession,
        private AddExamQuestion $addExamQuestion,
        private RetireCourse $retire,
    ) {}

    public function index(Request $request): View
    {
        $courses = Course::query()->ownedBy((int) $request->user()->id)->latest('id')->get();

        return view('courses::instructor.courses', ['courses' => $courses]);
    }

    public function create(): View
    {
        return view('courses::instructor.course-create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'string'],
        ]);

        try {
            $price = Money::fromInput($data['price']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['price' => $exception->getMessage()])->withInput();
        }

        $course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => $request->user()->id,
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price_toman' => $price->toman,
            'status' => CourseStatus::Draft,
        ]);

        return redirect()->route('courses.instructor.courses.edit', $course);
    }

    public function edit(Request $request, Course $course): View
    {
        $this->authorizeOwnership($request, $course);

        return view('courses::instructor.course-edit', [
            'course' => $course->load(['sessions', 'exam.questions.choices']),
        ]);
    }

    public function addSession(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwnership($request, $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content_type' => ['required', 'in:text,video'],
            'content' => ['nullable', 'string', 'max:10000'],
        ]);

        try {
            $this->addSession->handle($course, $data['title'], $data['content_type'], $data['content'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['session' => $exception->getMessage()]);
        }

        return redirect()->route('courses.instructor.courses.edit', $course);
    }

    public function addExamQuestion(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwnership($request, $course);

        $data = $request->validate([
            'text' => ['required', 'string', 'max:1000'],
            'choices' => ['required', 'array', 'min:2'],
            'choices.*' => ['nullable', 'string', 'max:255'],
            'correct' => ['required', 'integer', 'min:0'],
        ]);

        // اسلات‌های خالی فرم (گزینه سوم/چهارم اختیاری) پیش از رسیدن به اکشن
        // کنار گذاشته می‌شوند؛ تطبیق «کدام گزینه درست است» روی همان اندیس
        // خام فرم انجام می‌شود، نه اندیس بعد از فیلتر.
        $choices = [];

        foreach ($data['choices'] as $index => $text) {
            $text = trim((string) $text);

            if ($text === '') {
                continue;
            }

            $choices[] = ['text' => $text, 'is_correct' => $index === (int) $data['correct']];
        }

        try {
            $this->addExamQuestion->handle($course, $data['text'], $choices);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['exam' => $exception->getMessage()]);
        }

        return redirect()->route('courses.instructor.courses.edit', $course);
    }

    public function submit(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwnership($request, $course);

        try {
            $this->submit->handle($course);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['course' => $exception->getMessage()]);
        }

        return redirect()->route('courses.instructor.courses.edit', $course);
    }

    public function retire(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwnership($request, $course);

        try {
            $this->retire->handle($course);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['course' => $exception->getMessage()]);
        }

        return redirect()->route('courses.instructor.courses.edit', $course);
    }

    private function authorizeOwnership(Request $request, Course $course): void
    {
        abort_unless($course->instructor_user_id === $request->user()->id, 403, 'این دوره متعلق به شما نیست.');
    }
}
