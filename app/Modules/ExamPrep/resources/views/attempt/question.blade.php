<x-layouts.workspace :title="$attempt->pack->title"
                     :heading="$attempt->pack->title"
                     :lede="$attempt->mode->label().' · سؤال '.\App\Support\PersianDigits::from($position).' از '.\App\Support\PersianDigits::from($attempt->total())"
                     nav="my-exams">

    @error('answer')
        <x-alert tone="error" class="mb-6">{{ $message }}</x-alert>
    @enderror
    @error('choice')
        <x-alert tone="error" class="mb-6">{{ $message }}</x-alert>
    @enderror

    <form method="POST" action="{{ route('exam_prep.answer', $attempt) }}" class="flex flex-col gap-6">
        @csrf
        <input type="hidden" name="question" value="{{ $question->id }}">

        <fieldset>
            <legend class="text-h4 text-ink">
                <span class="mb-2 block text-note font-normal text-muted">{{ $question->topic->title }} · {{ $question->difficulty->label() }}</span>
                <span class="whitespace-pre-line">{{ $question->body }}</span>
            </legend>

            <div class="mt-5 flex flex-col divide-y divide-line border-y border-line">
                @foreach ($question->choices as $choice)
                    <label class="flex min-h-touch cursor-pointer items-center gap-3 py-3 text-copy text-body">
                        <input type="radio" name="choice" value="{{ $choice->id }}" required class="size-5 shrink-0 accent-primary">
                        {{ $choice->body }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div>
            <x-button type="submit" variant="primary">ثبت پاسخ</x-button>
        </div>
    </form>

</x-layouts.workspace>
