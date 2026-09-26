<x-layouts.workspace :title="$attempt->pack->title"
                     :heading="$attempt->pack->title"
                     :lede="$attempt->mode->label().' · سؤال '.\App\Support\PersianDigits::from($position).' از '.\App\Support\PersianDigits::from($attempt->total())"
                     nav="my-exams">

    @php $right = $answer?->is_correct === true; @endphp

    <x-alert :tone="$right ? 'success' : 'error'" :title="$right ? 'درست است' : 'نادرست'" class="mb-6" role="status">
        @unless ($right)
            پاسخ درست: {{ $question->correctChoice()?->body }}
        @endunless
    </x-alert>

    <p class="text-note text-muted">{{ $question->topic->title }} · {{ $question->difficulty->label() }}</p>
    <p class="mt-2 whitespace-pre-line text-h4 text-ink">{{ $question->body }}</p>

    <ul class="mt-5 flex list-none flex-col divide-y divide-line border-y border-line ps-0">
        @foreach ($question->choices as $choice)
            <li class="flex min-h-touch items-center gap-3 py-3 text-copy">
                @if ($choice->is_correct)
                    <x-icon name="check" class="size-5 shrink-0 text-primary" />
                    <span class="font-semibold text-ink">{{ $choice->body }}<span class="sr-only"> (پاسخ درست)</span></span>
                @elseif ($answer?->choice_id === $choice->id)
                    <x-icon name="close" class="size-5 shrink-0 text-danger" />
                    <span class="text-body">{{ $choice->body }}<span class="sr-only"> (پاسخ شما)</span></span>
                @else
                    <span class="size-5 shrink-0" aria-hidden="true"></span>
                    <span class="text-muted">{{ $choice->body }}</span>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($question->explanation)
        <h2 class="mt-8 text-h4 text-ink">توضیح</h2>
        <p class="mt-2 whitespace-pre-line text-copy text-body">{{ $question->explanation }}</p>
    @endif

    @if ($question->reference_path)
        <p class="mt-4 text-label">
            <a href="{{ url($question->reference_path) }}" class="inline-flex min-h-touch items-center">{{ $question->reference_label }}</a>
        </p>
    @endif

    <div class="mt-8">
        @if ($finished)
            <x-button :href="route('exam_prep.result', $attempt)" variant="primary">دیدن کارنامه</x-button>
        @else
            <x-button :href="route('exam_prep.attempt', $attempt)" variant="primary" icon="forward">سؤال بعدی</x-button>
        @endif
    </div>

</x-layouts.workspace>
