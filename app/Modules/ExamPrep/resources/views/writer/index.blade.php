@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="سؤال‌های آزمون من"
                     heading="سؤال‌های آزمون من"
                     lede="سؤال‌هایی که برای بسته‌های آمادگی آزمون نوشته‌اید، با وضعیت بررسی مدیر."
                     nav="exam-questions" help="exam-questions">

    <x-slot:actions>
        <x-button :href="route('exam_prep.writer.create')" variant="primary" icon="plus">سؤال تازه</x-button>
    </x-slot:actions>

    @if (session('status'))
        <x-alert tone="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if ($questions->isEmpty())
        <x-empty-state icon="list"
                       title="هنوز سؤالی ننوشته‌اید"
                       description="سؤال شما پس از تأیید مدیر در بسته منتشر می‌شود.">
            <x-slot:action>
                <x-button :href="route('exam_prep.writer.create')" variant="primary">سؤال تازه</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <ul class="flex list-none flex-col divide-y divide-line border-y border-line ps-0">
            @foreach ($questions as $question)
                <li class="flex flex-col gap-1.5 py-4">
                    <span class="flex flex-wrap items-center gap-2">
                        <x-badge :tone="$question->status->tone()">{{ $question->status->label() }}</x-badge>
                        <span class="text-note text-muted">{{ $question->pack->title }} · {{ $question->topic->title }} · {{ JalaliDate::short($question->created_at) }}</span>
                    </span>
                    <span class="text-label text-ink">{{ \Illuminate\Support\Str::limit($question->body, 160) }}</span>
                    @if ($question->review_note)
                        <span class="text-note text-danger-ink">یادداشت مدیر: {{ $question->review_note }}</span>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $questions->links() }}</div>
    @endif

</x-layouts.workspace>
