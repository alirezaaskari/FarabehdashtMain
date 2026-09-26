<x-layouts.public :title="$course->title" description="محیط یادگیری دوره." active="courses">

    <x-page-header art="courses-learn" :title="$course->title" lede="پیشرفت خود را این‌جا دنبال کنید." />

    @if ($enrollment->completed_at)
        <x-alert tone="success" title="دوره را تکمیل کرده‌اید" class="mt-6" />
    @endif

    @if ($errors->any())
        <x-alert tone="error" title="مشکلی پیش آمد" class="mt-6">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <x-card size="lg" class="mt-6">
        <h2 class="text-h4 text-ink">جلسه‌ها</h2>

        <ul class="mt-4 divide-y divide-line">
            @foreach ($course->sessions as $session)
                @php $done = $enrollment->hasCompletedSession($session->id); @endphp
                <li class="flex items-center justify-between gap-4 py-4">
                    <div>
                        <p class="text-label font-semibold text-ink">{{ $session->title }}</p>
                        @if ($session->content_type === 'text' && $session->content)
                            <p class="mt-1 text-label text-muted">{{ $session->content }}</p>
                        @elseif ($session->content)
                            <a href="{{ $session->content }}" class="mt-1 inline-block text-label text-primary" target="_blank" rel="noopener">
                                مشاهده محتوا
                            </a>
                        @endif
                    </div>

                    @if ($done)
                        <x-badge tone="primary">دیده‌شده</x-badge>
                    @else
                        <form method="POST" action="{{ route('courses.sessions.complete', [$course, $session]) }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm">تکمیل جلسه</x-button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-card>

    @if ($course->exam)
        <x-card size="lg" class="mt-6">
            <h2 class="text-h4 text-ink">آزمون</h2>
            <p class="mt-1 text-label text-muted">
                نمره قبولی: <span dir="ltr" data-numeric>{{ $course->exam->pass_percentage }}</span>٪
                @if ($enrollment->bestExamScore() !== null)
                    · بهترین نمره شما: <span dir="ltr" data-numeric>{{ $enrollment->bestExamScore() }}</span>٪
                @endif
            </p>

            <form method="POST" action="{{ route('courses.exam.submit', $course) }}" class="mt-4 flex flex-col gap-5">
                @csrf

                @foreach ($course->exam->questions as $question)
                    <fieldset class="border-t border-line pt-4">
                        <legend class="text-label font-semibold text-ink">{{ $question->text }}</legend>

                        <div class="mt-2 flex flex-col gap-2">
                            @foreach ($question->choices as $choice)
                                <label class="flex items-center gap-2 text-label text-body">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $choice->id }}">
                                    {{ $choice->text }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <div>
                    <x-button type="submit" variant="primary">ثبت پاسخ‌ها</x-button>
                </div>
            </form>
        </x-card>
    @endif

    <x-card size="lg" class="mt-6">
        <h2 class="text-h4 text-ink">دیدگاه شما</h2>

        @if ($enrollment->review)
            <p class="mt-2 text-label text-body">
                امتیاز: <span dir="ltr" data-numeric>{{ $enrollment->review->rating }}</span>/۵
            </p>
            @if ($enrollment->review->comment)
                <p class="mt-1 text-label text-muted">{{ $enrollment->review->comment }}</p>
            @endif
        @elseif ($enrollment->completed_at)
            <form method="POST" action="{{ route('courses.review.store', $course) }}" class="mt-4 flex flex-col gap-4">
                @csrf

                <x-field name="rating" label="امتیاز (۱ تا ۵)" type="number" min="1" max="5" numeric required />

                <div>
                    <label for="comment" class="mb-2 block text-label font-semibold text-ink">دیدگاه (اختیاری)</label>
                    <textarea id="comment" name="comment" rows="3"
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink"></textarea>
                </div>

                <div>
                    <x-button type="submit" variant="primary">ثبت دیدگاه</x-button>
                </div>
            </form>
        @else
            <p class="mt-2 text-label text-muted">پس از تکمیل دوره می‌توانید دیدگاه ثبت کنید.</p>
        @endif
    </x-card>

</x-layouts.public>
