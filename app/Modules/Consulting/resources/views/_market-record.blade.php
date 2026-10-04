@php use App\Support\JalaliDate; @endphp

<x-card title="در بازار پروژه فرابهداشت" heading="text-h4">
    <p class="text-copy text-body">@fa($record->completed) پروژه تحویل‌شده با پرداخت امانی</p>
    <p class="mt-2 text-copy text-body">
        @if ($record->average !== null)
            میانگین {{ $record->averageLabel() }} از ۵، از @fa($record->ratings) امتیاز کارفرما
        @elseif ($record->ratings > 0)
            @fa($record->ratings) امتیاز کارفرما؛ میانگین از @fa($record->averageMin) امتیاز نشان داده می‌شود.
        @else
            هنوز امتیازی از کارفرما ندارد.
        @endif
    </p>
    @if ($record->quotes !== [])
        <ul class="mt-4 flex list-none flex-col gap-3 border-t border-line pt-4 ps-0">
            @foreach ($record->quotes as $quote)
                <li>
                    <p class="text-copy text-body">«{{ $quote->comment }}»</p>
                    <p class="mt-1 text-note text-muted">@fa($quote->stars) از ۵ · {{ JalaliDate::short($quote->at) }}</p>
                </li>
            @endforeach
        </ul>
    @endif
    <p class="mt-4 text-note text-muted">فقط از قراردادهای واقعی همین سایت؛ نشان کیفیت رسمی نیست.</p>
</x-card>
