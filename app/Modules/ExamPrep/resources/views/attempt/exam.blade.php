<x-layouts.workspace :title="$attempt->pack->title"
                     :heading="$attempt->pack->title"
                     lede="آزمون زمان‌دار: پاسخ‌ها پس از ارسال در کارنامه دیده می‌شوند. با پایان زمان، فرم خودش فرستاده می‌شود."
                     nav="my-exams">

    <form method="POST" action="{{ route('exam_prep.submit', $attempt) }}" data-exam-timer="{{ $secondsLeft }}" class="flex flex-col gap-8">
        @csrf

        <div class="sticky top-0 z-10 -mx-1 flex flex-wrap items-center justify-between gap-3 border-b border-line bg-ground px-1 py-3">
            <p class="flex items-center gap-2 text-label text-ink">
                <x-icon name="clock" class="size-5 text-muted" />
                زمان باقی‌مانده:
                <span data-exam-timer-clock class="font-semibold">@fa(intdiv($secondsLeft, 60)) دقیقه</span>
            </p>
            <p class="text-note text-muted">@fa($questions->count()) سؤال</p>
            <p data-exam-timer-notice class="sr-only" aria-live="polite"></p>
        </div>

        @foreach ($questions as $question)
            <fieldset class="border-b border-line pb-6">
                <legend class="text-copy font-semibold text-ink">
                    <span class="text-muted">@fa($loop->iteration).</span>
                    <span class="whitespace-pre-line">{{ $question->body }}</span>
                </legend>

                <div class="mt-3 flex flex-col">
                    @foreach ($question->choices as $choice)
                        <label class="flex min-h-touch cursor-pointer items-center gap-3 text-copy text-body">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $choice->id }}" class="size-5 shrink-0 accent-primary">
                            {{ $choice->body }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <div>
            <x-button type="submit" variant="primary">پایان و ثبت آزمون</x-button>
        </div>
    </form>

</x-layouts.workspace>
