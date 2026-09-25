@php
    use App\Modules\Courses\Domain\Enums\CourseStatus;
@endphp

<x-layouts.workspace :title="$course->title" nav="instructor-courses">

    <x-page-header :title="$course->title" :lede="$course->price()->format()">
        <x-slot:actions>
            <x-badge :tone="$course->status->tone()">{{ $course->status->label() }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <x-alert tone="error" title="مشکلی پیش آمد" class="mt-6">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @if ($course->status === CourseStatus::Rejected && $course->review_note)
        <x-alert tone="caution" title="دلیل رد شدن" class="mt-6">{{ $course->review_note }}</x-alert>
    @endif

    @if ($course->status === CourseStatus::Published)
        <x-alert tone="info" class="mt-6">
            جلسه یا سؤالی که به دوره منتشرشده اضافه کنید، پس از تأیید مدیر به دانشجوها نشان داده می‌شود.
        </x-alert>
    @endif

    <x-card size="lg" class="mt-6">
        <h2 class="text-h4 text-ink">جلسه‌ها</h2>

        @if ($course->sessions->isEmpty())
            <p class="mt-3 text-label text-muted">هنوز جلسه‌ای اضافه نشده است.</p>
        @else
            <ol class="mt-4 list-inside list-decimal divide-y divide-line">
                @foreach ($course->sessions as $session)
                    <li class="py-3 text-label font-bold text-ink">
                        {{ $session->title }}
                        @if ($course->status === CourseStatus::Published && ! $session->isApproved())
                            <x-badge tone="caution" class="ms-2">در انتظار تأیید مدیر</x-badge>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif

        @if ($course->status !== CourseStatus::Retired)
            <form method="POST" action="{{ route('courses.instructor.courses.sessions', $course) }}"
                  class="mt-6 flex flex-col gap-4 border-t border-line pt-6">
                @csrf

                <x-field name="title" label="عنوان جلسه" required />

                <div>
                    <label for="content_type" class="mb-2 block text-label font-bold text-ink">نوع محتوا</label>
                    <select id="content_type" name="content_type"
                            class="h-field w-full rounded-md border border-line-strong bg-surface px-3 text-control text-ink">
                        <option value="text">متن</option>
                        <option value="video">ویدیو (نشانی)</option>
                    </select>
                </div>

                <div>
                    <label for="content" class="mb-2 block text-label font-bold text-ink">محتوا</label>
                    <textarea id="content" name="content" rows="3"
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink"></textarea>
                </div>

                <div>
                    <x-button type="submit" variant="secondary">افزودن جلسه</x-button>
                </div>
            </form>
        @endif
    </x-card>

    <x-card size="lg" class="mt-6">
        <h2 class="text-h4 text-ink">آزمون</h2>

        @if (! $course->exam || $course->exam->questions->isEmpty())
            <p class="mt-3 text-label text-muted">هنوز سؤالی اضافه نشده است — آزمون اختیاری است.</p>
        @else
            <ul class="mt-4 divide-y divide-line">
                @foreach ($course->exam->questions as $question)
                    <li class="py-3 text-label text-ink">
                        {{ $question->text }}
                        @if ($course->status === CourseStatus::Published && ! $question->isApproved())
                            <x-badge tone="caution" class="ms-2">در انتظار تأیید مدیر</x-badge>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($course->status !== CourseStatus::Retired)
            <form method="POST" action="{{ route('courses.instructor.courses.questions', $course) }}"
                  class="mt-6 flex flex-col gap-4 border-t border-line pt-6">
                @csrf

                <x-field name="text" label="متن سؤال" required />

                <div class="flex flex-col gap-2">
                    <p class="text-label font-bold text-ink">گزینه‌ها (گزینه درست را انتخاب کنید)</p>
                    @for ($i = 0; $i < 4; $i++)
                        <div class="flex items-center gap-2">
                            <input type="radio" name="correct" value="{{ $i }}" @if ($i === 0) checked @endif>
                            <input type="text" name="choices[]" placeholder="گزینه {{ $i + 1 }}"
                                   @if ($i < 2) required @endif
                                   class="h-field grow rounded-md border border-line-strong bg-surface px-3 text-control text-ink">
                        </div>
                    @endfor
                </div>

                <div>
                    <x-button type="submit" variant="secondary">افزودن سؤال</x-button>
                </div>
            </form>
        @endif
    </x-card>

    <x-card size="lg" class="mt-6">
        <h2 class="text-h4 text-ink">انتشار</h2>

        <div class="mt-4 flex flex-wrap gap-3">
            @if (in_array($course->status, [CourseStatus::Draft, CourseStatus::Rejected], true))
                <form method="POST" action="{{ route('courses.instructor.courses.submit', $course) }}">
                    @csrf
                    <x-button type="submit" variant="primary">ارسال برای بررسی</x-button>
                </form>
            @endif

            @if ($course->status === CourseStatus::Published)
                <form method="POST" action="{{ route('courses.instructor.courses.retire', $course) }}">
                    @csrf
                    <x-button type="submit" variant="danger">بستن ثبت‌نام تازه</x-button>
                </form>
            @endif
        </div>
    </x-card>

</x-layouts.workspace>
