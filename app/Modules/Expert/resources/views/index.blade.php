@php use App\Support\JalaliDate; @endphp

<x-layouts.public title="پرسش از متخصص"
                  description="پرسش‌های بهداشت حرفه‌ای و پاسخ مشاوران تأییدشده فرابهداشت؛ صدا، گرما، مواد شیمیایی، تهویه، ارگونومی و بیشتر."
                  :canonical="route('expert.index')"
                  active="expert">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['پرسش از متخصص', null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="expert-index" title="پرسش از متخصص"
                   lede="پرسش تخصصی بهداشت حرفه‌ای را بپرسید؛ مشاوران تأییدشده فرابهداشت پاسخ می‌دهند و هر پرسش و پاسخ پیش از انتشار بررسی می‌شود.">
        <x-slot:actions>
            <x-button :href="route('expert.create')" variant="primary" icon="plus">پرسش تازه</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-page-help topic="expert" class="mt-5" />

    <nav aria-label="حوزه‌ها" class="mt-8 flex flex-wrap gap-2">
        <a href="{{ route('expert.index') }}"
           @class([
               'inline-flex min-h-touch items-center rounded-md border px-4 text-label no-underline hover:no-underline',
               'border-primary bg-primary-soft font-semibold text-on-primary-soft' => $topic === null,
               'border-line bg-surface text-muted hover:bg-surface-2' => $topic !== null,
           ])
           @if ($topic === null) aria-current="page" @endif>همه</a>
        @foreach ($topics as $item)
            <a href="{{ route('expert.index', ['topic' => $item->value]) }}"
               @class([
                   'inline-flex min-h-touch items-center rounded-md border px-4 text-label no-underline hover:no-underline',
                   'border-primary bg-primary-soft font-semibold text-on-primary-soft' => $topic === $item,
                   'border-line bg-surface text-muted hover:bg-surface-2' => $topic !== $item,
               ])
               @if ($topic === $item) aria-current="page" @endif>{{ $item->label() }}</a>
        @endforeach
    </nav>

    <div class="mt-6">
        @if ($questions->isEmpty())
            <x-empty-state art="empty-expert-index" icon="bulb"
                           title="هنوز پرسشی در این حوزه منتشر نشده"
                           description="نخستین پرسش را شما بپرسید؛ پس از تأیید مدیر، مشاوران تأییدشده پاسخ می‌دهند.">
                <x-slot:action>
                    <x-button :href="route('expert.create')" variant="primary">پرسش تازه</x-button>
                </x-slot:action>
            </x-empty-state>
        @else
            <ul class="flex list-none flex-col gap-3 ps-0">
                @foreach ($questions as $question)
                    <li>
                        <a href="{{ route('expert.show', $question->uuid) }}"
                           class="flex min-h-touch flex-col gap-1.5 rounded-xl border border-line bg-surface px-5 py-4 no-underline hover:bg-surface-2 hover:no-underline">
                            <span class="text-h4 text-ink">{{ $question->title }}</span>
                            <span class="flex flex-wrap items-center gap-2 text-note text-muted">
                                <span>{{ $question->topic->label() }}</span>
                                <span aria-hidden="true">·</span>
                                <span>{{ JalaliDate::short($question->published_at ?? $question->created_at) }}</span>
                                <span aria-hidden="true">·</span>
                                <span>@fa($question->published_answers_count) پاسخ</span>
                                @if ($question->isAnswered())
                                    <x-badge tone="primary" icon="check">پاسخ‌گرفته</x-badge>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $questions->links() }}</div>
        @endif
    </div>

</x-layouts.public>
