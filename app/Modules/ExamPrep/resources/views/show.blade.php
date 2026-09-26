@php use App\Modules\ExamPrep\Domain\Enums\AttemptMode; use App\Support\JalaliDate; @endphp

<x-layouts.public :title="$pack->title"
                  :description="\Illuminate\Support\Str::limit($pack->description, 155)"
                  :canonical="route('exam_prep.show', $pack->slug)"
                  active="exam-prep">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['آمادگی آزمون', route('exam_prep.index')], [$pack->title, null]]" />
    </x-slot:breadcrumb>

    <x-page-header :title="$pack->title" :lede="$pack->exam_name" />

    @if (session('status'))
        <x-alert tone="success" class="mt-6">{{ session('status') }}</x-alert>
    @endif

    @if ($errors->any())
        <x-alert tone="error" title="انجام نشد" class="mt-6">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </x-alert>
    @endif

    <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0">
            <p class="whitespace-pre-line text-copy text-body">{{ $pack->description }}</p>

            <h2 class="mt-10 text-h3 text-ink">موضوع‌ها</h2>
            <ul class="mt-4 flex list-none flex-col divide-y divide-line border-y border-line ps-0">
                @foreach ($pack->topics as $topic)
                    <li class="flex min-h-touch flex-wrap items-center justify-between gap-3 py-3">
                        <span class="text-label text-ink">{{ $topic->title }}</span>
                        <span class="flex items-center gap-3">
                            <span class="text-note text-muted">@fa($topic->published_count) سؤال</span>
                            @if ($owns && $topic->published_count > 0)
                                <form method="POST" action="{{ route('exam_prep.start', $pack->slug) }}">
                                    @csrf
                                    <input type="hidden" name="mode" value="{{ AttemptMode::Practice->value }}">
                                    <input type="hidden" name="topic" value="{{ $topic->id }}">
                                    <x-button type="submit" variant="ghost" size="sm">تمرین این موضوع</x-button>
                                </form>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>

            @if ($attempts->isNotEmpty())
                <h2 class="mt-10 text-h3 text-ink">دورهای اخیر شما</h2>
                <ul class="mt-4 flex list-none flex-col divide-y divide-line border-y border-line ps-0">
                    @foreach ($attempts as $attempt)
                        <li>
                            <a href="{{ route('exam_prep.result', $attempt) }}"
                               class="flex min-h-touch items-center justify-between gap-3 py-3 no-underline hover:no-underline">
                                <span class="text-label text-ink">{{ $attempt->mode->label() }} · {{ JalaliDate::short($attempt->submitted_at) }}</span>
                                <span class="text-note text-muted">@fa($attempt->correct_count) از @fa($attempt->total()) درست</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <aside class="flex flex-col gap-6 lg:border-s lg:border-line lg:ps-8">
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-note text-muted">سؤال منتشرشده</dt>
                    <dd class="mt-1 text-h4 text-ink">@fa($questionCount)</dd>
                </div>
                <div>
                    <dt class="text-note text-muted">آزمون شبیه‌سازی</dt>
                    <dd class="mt-1 text-h4 text-ink">@fa($pack->exam_question_count) سؤال، @fa($pack->exam_minutes) دقیقه</dd>
                </div>
            </dl>

            @auth
                @if ($owns)
                    <x-badge tone="primary" icon="check">خریده‌اید</x-badge>
                    <div class="flex flex-col gap-2">
                        <form method="POST" action="{{ route('exam_prep.start', $pack->slug) }}">
                            @csrf
                            <input type="hidden" name="mode" value="{{ AttemptMode::Exam->value }}">
                            <x-button type="submit" variant="primary" icon="clock" class="w-full">شروع آزمون زمان‌دار</x-button>
                        </form>
                        <form method="POST" action="{{ route('exam_prep.start', $pack->slug) }}">
                            @csrf
                            <input type="hidden" name="mode" value="{{ AttemptMode::Practice->value }}">
                            <x-button type="submit" variant="secondary" class="w-full">تمرین از همه موضوع‌ها</x-button>
                        </form>
                    </div>
                @else
                    <p class="text-h3 text-ink">{{ $pack->price()->format() }}</p>

                    @if ($onSale)
                        <form method="POST" action="{{ route('exam_prep.purchase', $pack->slug) }}" class="flex flex-col gap-3">
                            @csrf
                            <x-payment-method :total="$pack->price()" />
                            <x-button type="submit" variant="primary">خرید بسته</x-button>
                        </form>
                    @else
                        <p class="text-note text-muted">فروش این بسته در حال حاضر بسته است.</p>
                    @endif

                    @if ($sampleCount > 0)
                        <form method="POST" action="{{ route('exam_prep.start', $pack->slug) }}">
                            @csrf
                            <input type="hidden" name="mode" value="{{ AttemptMode::Sample->value }}">
                            <x-button type="submit" variant="secondary" class="w-full">@fa($sampleCount) سؤال نمونه رایگان</x-button>
                        </form>
                    @endif
                @endif
            @else
                <p class="text-h3 text-ink">{{ $pack->price()->format() }}</p>
                <x-button :href="Route::has('login') ? route('login') : url('/')" variant="primary">ورود برای خرید یا نمونه رایگان</x-button>
            @endauth

            <p class="text-note text-muted">
                این بسته برای تمرین و سنجیدن خودتان است. نتیجه آزمون واقعی به آمادگی خود شما بستگی دارد و فرابهداشت
                درباره آن وعده‌ای نمی‌دهد.
            </p>
        </aside>
    </div>

</x-layouts.public>
