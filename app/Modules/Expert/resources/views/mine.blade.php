@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="expert-mine" title="پرسش‌های من"
                     heading="پرسش‌های من"
                     lede="پرسش‌هایی که از مشاوران تأییدشده پرسیده‌اید، وضعیت تأیید و شمار پاسخ‌ها."
                     nav="my-questions" help="expert-mine">

    <x-slot:actions>
        <x-button :href="route('expert.create')" variant="primary" icon="plus">پرسش تازه</x-button>
    </x-slot:actions>

    @if ($questions->isEmpty())
        <x-empty-state art="empty-expert-mine" icon="bulb"
                       title="هنوز پرسشی نپرسیده‌اید"
                       description="پرسش تخصصی بهداشت حرفه‌ای را بپرسید؛ پس از تأیید مدیر، مشاوران تأییدشده پاسخ می‌دهند.">
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
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-label font-semibold text-ink">{{ $question->title }}</span>
                            <x-badge :tone="$question->status->tone()">{{ $question->status->label() }}</x-badge>
                            @if ($question->isAnswered())
                                <x-badge tone="primary" icon="check">پاسخ‌گرفته</x-badge>
                            @endif
                        </span>
                        <span class="text-note text-muted">
                            {{ $question->topic->label() }} · {{ $question->visibility->label() }} · {{ JalaliDate::short($question->created_at) }}
                            · @fa($question->published_answers_count) پاسخ
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $questions->links() }}</div>
    @endif

</x-layouts.workspace>
