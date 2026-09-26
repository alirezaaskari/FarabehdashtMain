<x-layouts.workspace title="کارنامه"
                     :heading="'کارنامه — '.$attempt->pack->title"
                     :lede="$attempt->mode->label().($attempt->topic ? ' · '.$attempt->topic->title : '')"
                     nav="my-exams">

    @if (session('status'))
        <x-alert tone="{{ $attempt->late ? 'caution' : 'success' }}" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <dl class="grid grid-cols-2 gap-6 border-y border-line py-5 sm:grid-cols-3">
        <div>
            <dt class="text-note text-muted">نمره</dt>
            <dd class="mt-1 text-h3 text-ink">@fa($card['percent'])٪</dd>
        </div>
        <div>
            <dt class="text-note text-muted">پاسخ درست</dt>
            <dd class="mt-1 text-h3 text-ink">@fa($card['correct']) از @fa($card['total'])</dd>
        </div>
        @if ($attempt->late)
            <div>
                <dt class="text-note text-muted">وضعیت</dt>
                <dd class="mt-1"><x-badge tone="caution">پس از زمان</x-badge></dd>
            </div>
        @endif
    </dl>

    <h2 class="mt-10 text-h3 text-ink">موضوع‌ها، از ضعیف‌ترین</h2>
    <ul class="mt-4 flex list-none flex-col divide-y divide-line border-y border-line ps-0">
        @foreach ($card['topics'] as $topic)
            <li class="flex min-h-touch flex-wrap items-center justify-between gap-3 py-3">
                <span class="flex items-center gap-2 text-label text-ink">
                    {{ $topic['title'] }}
                    @if ($topic['weak'])
                        <x-badge tone="caution">نیازمند مرور</x-badge>
                    @endif
                </span>
                <span class="text-note text-muted">@fa($topic['correct']) از @fa($topic['total']) · @fa($topic['percent'])٪</span>
            </li>
        @endforeach
    </ul>

    @if ($card['mistakes'] !== [])
        <h2 class="mt-10 text-h3 text-ink">پاسخ‌های نادرست</h2>
        <ol class="mt-4 flex list-none flex-col divide-y divide-line border-y border-line ps-0">
            @foreach ($card['mistakes'] as $mistake)
                @php $question = $mistake['question']; @endphp
                <li class="py-5">
                    <p class="text-note text-muted">{{ $question->topic->title }}</p>
                    <p class="mt-1 whitespace-pre-line text-copy font-semibold text-ink">{{ $question->body }}</p>
                    <p class="mt-2 text-label text-body">پاسخ شما: {{ $mistake['chosen'] ?? 'بی‌پاسخ' }}</p>
                    <p class="text-label text-ink">پاسخ درست: <span class="font-semibold">{{ $mistake['correct'] }}</span></p>
                    @if ($question->explanation)
                        <p class="mt-2 whitespace-pre-line text-label text-muted">{{ $question->explanation }}</p>
                    @endif
                    <p class="mt-1 text-label">
                        @if ($question->reference_path)
                            <a href="{{ url($question->reference_path) }}" class="inline-flex min-h-touch items-center">{{ $question->reference_label }}</a>
                        @elseif (Route::has('search'))
                            <a class="inline-flex min-h-touch items-center" href="{{ route('search', ['q' => $question->topic->title]) }}">مطالعه درباره «{{ $question->topic->title }}»</a>
                        @endif
                    </p>
                </li>
            @endforeach
        </ol>
    @endif

    <div class="mt-8 flex flex-wrap gap-2">
        <x-button :href="route('exam_prep.show', $attempt->pack->slug)" variant="primary">بازگشت به بسته</x-button>
        <x-button :href="route('exam_prep.mine')" variant="secondary">آزمون‌های من</x-button>
    </div>

</x-layouts.workspace>
