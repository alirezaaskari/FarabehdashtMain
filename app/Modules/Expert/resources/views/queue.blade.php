@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="expert-queue" title="پرسش‌های باز برای پاسخ"
                     heading="پرسش‌های باز برای پاسخ"
                     lede="پرسش‌های تأییدشده‌ای که هنوز پاسخ شما را ندارند. پرسش مشترکان حرفه‌ای بالای فهرست است."
                     nav="expert-queue" help="expert-queue">

    @if ($answers->isNotEmpty())
        <x-card title="پاسخ‌های اخیر شما" heading="text-h4" class="mb-6">
            <ul class="flex list-none flex-col gap-2 ps-0">
                @foreach ($answers as $answer)
                    <li class="flex flex-wrap items-center justify-between gap-2">
                        <a href="{{ route('expert.show', $answer->question->uuid) }}" class="inline-flex min-h-touch items-center text-label font-semibold">
                            {{ $answer->question->title }}
                        </a>
                        <x-badge :tone="$answer->status->tone()">{{ $answer->status->label() }}</x-badge>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    @if ($questions->isEmpty())
        <x-empty-state art="empty-expert-queue" icon="check"
                       title="پرسش بازی برای شما نمانده"
                       description="به همه پرسش‌های تأییدشده پاسخ داده‌اید یا پرسش تازه‌ای نیامده. پرسش‌های تازه پس از تأیید مدیر این‌جا می‌آیند." />
    @else
        <ul class="flex list-none flex-col gap-3 ps-0">
            @foreach ($questions as $question)
                <li>
                    <a href="{{ route('expert.show', $question->uuid) }}#answer-form-heading"
                       class="flex min-h-touch flex-col gap-1.5 rounded-xl border border-line bg-surface px-5 py-4 no-underline hover:bg-surface-2 hover:no-underline">
                        <span class="flex flex-wrap items-center gap-2">
                            @if ($question->priority)
                                <x-badge tone="caution" icon="badge">اولویت حرفه‌ای</x-badge>
                            @endif
                            <span class="text-label font-semibold text-ink">{{ $question->title }}</span>
                        </span>
                        <span class="text-note text-muted">
                            {{ $question->topic->label() }} · {{ $question->visibility->label() }} · {{ JalaliDate::short($question->published_at ?? $question->created_at) }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $questions->links() }}</div>
    @endif

</x-layouts.workspace>
