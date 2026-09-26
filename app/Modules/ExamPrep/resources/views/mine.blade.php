@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="آزمون‌های من"
                     heading="آزمون‌های من"
                     lede="بسته‌های آمادگی آزمونی که خریده‌اید و کارنامه دورهای اخیر."
                     nav="my-exams" help="my-exams">

    <x-slot:actions>
        <x-button :href="route('exam_prep.index')" variant="primary">بسته‌های آزمون</x-button>
    </x-slot:actions>

    <h2 class="text-h3 text-ink">بسته‌های من</h2>
    @if ($purchases->isEmpty())
        <x-empty-state icon="list" class="mt-4"
                       title="هنوز بسته‌ای نخریده‌اید"
                       description="نمونه رایگان هر بسته را بدون خرید امتحان کنید." />
    @else
        <ul class="mt-4 flex list-none flex-col divide-y divide-line border-y border-line ps-0">
            @foreach ($purchases as $purchase)
                <li>
                    <a href="{{ route('exam_prep.show', $purchase->pack->slug) }}"
                       class="flex min-h-touch items-center justify-between gap-3 py-3 no-underline hover:no-underline">
                        <span class="text-label font-semibold text-ink">{{ $purchase->pack->title }}</span>
                        <span class="text-note text-muted">خرید {{ JalaliDate::short($purchase->paid_at) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($attempts->isNotEmpty())
        <h2 class="mt-10 text-h3 text-ink">دورهای اخیر</h2>
        <ul class="mt-4 flex list-none flex-col divide-y divide-line border-y border-line ps-0">
            @foreach ($attempts as $attempt)
                <li>
                    <a href="{{ $attempt->isSubmitted() ? route('exam_prep.result', $attempt) : route('exam_prep.attempt', $attempt) }}"
                       class="flex min-h-touch flex-wrap items-center justify-between gap-3 py-3 no-underline hover:no-underline">
                        <span class="text-label text-ink">{{ $attempt->pack->title }} · {{ $attempt->mode->label() }}</span>
                        <span class="text-note text-muted">
                            @if ($attempt->isSubmitted())
                                @fa($attempt->correct_count) از @fa($attempt->total()) درست · {{ JalaliDate::short($attempt->submitted_at) }}
                            @else
                                ادامه دور ناتمام
                            @endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

</x-layouts.workspace>
