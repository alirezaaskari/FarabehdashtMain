@php use App\Modules\Consulting\Domain\Enums\OrderStatus; @endphp

{{--
    بررسی گزارش (بخش ۱۹-۴): نتیجه بررسی، پرسش تکمیلی و متن فقط‌خواندنی
    گزارش. زیر هر نظر متخصص همان جمله ثابت می‌آید؛ گزارش هیچ مهر یا نشان
    «تأییدشده» نمی‌گیرد.
--}}
@php
    $disclaimer = 'این نظر کارشناسی است؛ تأیید رسمی گزارش یا انطباق قانونی نیست.';
    $writing = $isConsultant && $order->status === OrderStatus::Accepted;
    $sections = $report?->sections ?? [];
@endphp

@if ($order->reviewSummary())
    <x-card title="نظر متخصص" heading="text-h4">
        <div class="flex flex-col gap-4">
            @foreach ($order->reviewNotes() as $key => $note)
                <div>
                    <h3 class="text-label font-semibold text-ink">{{ $sections[$key] ?? $key }}</h3>
                    <p class="mt-1 whitespace-pre-line text-copy text-body">{{ $note }}</p>
                </div>
            @endforeach
            <div>
                <h3 class="text-label font-semibold text-ink">جمع‌بندی</h3>
                <p class="mt-1 whitespace-pre-line text-copy text-body">{{ $order->reviewSummary() }}</p>
            </div>
            @if ($order->follow_up_question)
                <div class="border-t border-line pt-4">
                    <h3 class="text-label font-semibold text-ink">پرسش تکمیلی خریدار</h3>
                    <p class="mt-1 whitespace-pre-line text-copy text-body">{{ $order->follow_up_question }}</p>
                    @if ($order->follow_up_answer)
                        <h3 class="mt-3 text-label font-semibold text-ink">پاسخ متخصص</h3>
                        <p class="mt-1 whitespace-pre-line text-copy text-body">{{ $order->follow_up_answer }}</p>
                    @endif
                </div>
            @endif
            <p class="text-note text-muted">{{ $disclaimer }}</p>
        </div>

        @if (! $isConsultant && $order->status === OrderStatus::Delivered && $order->follow_up_question === null)
            <form method="POST" action="{{ route('consulting.orders.follow-up', $order->uuid) }}" class="mt-6 flex flex-col gap-3 border-t border-line pt-5">
                @csrf
                <label for="question" class="text-label font-semibold text-ink">یک پرسش تکمیلی</label>
                <textarea id="question" name="question" rows="3" required aria-describedby="question-hint"
                          class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('question') }}</textarea>
                <p id="question-hint" class="text-note text-muted">فقط یک بار. تا پاسخ متخصص، مهلت آزادسازی خودکار می‌ایستد.</p>
                <div><x-button type="submit" variant="secondary">فرستادن پرسش</x-button></div>
            </form>
        @endif

        @if ($isConsultant && $order->status === OrderStatus::FollowUp)
            <form method="POST" action="{{ route('consulting.orders.answer', $order->uuid) }}" class="mt-6 flex flex-col gap-3 border-t border-line pt-5">
                @csrf
                <label for="answer" class="text-label font-semibold text-ink">پاسخ پرسش تکمیلی</label>
                <textarea id="answer" name="answer" rows="5" required
                          class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('answer') }}</textarea>
                <div><x-button type="submit" variant="primary">فرستادن پاسخ</x-button></div>
            </form>
        @endif
    </x-card>
@endif

@if ($writing)
    <x-card title="یادداشت‌های بررسی" heading="text-h4">
        <p class="text-copy text-muted">
            روی هر بخشی که نظر دارید یادداشت بنویسید؛ بخش بی‌یادداشت خالی بماند. جمع‌بندی لازم است. پس از فرستادن ویرایش نمی‌شود.
        </p>
        <form method="POST" action="{{ route('consulting.orders.review', $order->uuid) }}" class="mt-4 flex flex-col gap-4">
            @csrf
            @foreach ($sections as $key => $title)
                <div>
                    <label for="note-{{ $key }}" class="mb-2 block text-label font-semibold text-ink">{{ $title }}</label>
                    <textarea id="note-{{ $key }}" name="notes[{{ $key }}]" rows="3"
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('notes.'.$key) }}</textarea>
                </div>
            @endforeach
            <div>
                <label for="summary" class="mb-2 block text-label font-semibold text-ink">جمع‌بندی <span class="text-danger" aria-hidden="true">*</span></label>
                <textarea id="summary" name="summary" rows="6" required aria-describedby="summary-hint"
                          @if ($errors->has('summary')) aria-invalid="true" @endif
                          class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('summary') }}</textarea>
                <p id="summary-hint" class="mt-1 text-note text-muted">مهم‌ترین نکته‌ها و پیشنهادها. ننویسید گزارش «تأیید» یا «منطبق با قانون» است؛ زیر نظر شما همیشه می‌آید: {{ $disclaimer }}</p>
                @error('summary')
                    <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div><x-button type="submit" variant="primary" icon="check">فرستادن بررسی</x-button></div>
        </form>
    </x-card>
@endif

<x-card title="متن گزارش" heading="text-h4">
    @if ($report === null)
        <p class="text-copy text-muted">متن این گزارش فعلاً در دسترس نیست.</p>
    @else
        <p class="text-note text-muted">
            نسخه فقط‌خواندنی گزارش <span dir="ltr" data-numeric>{{ $report->trackingCode }}</span>.
            @unless ($report->valid) این گزارش پس از درخواست باطل یا با نسخه تازه جایگزین شده است. @endunless
        </p>
        <details class="mt-4 rounded-lg border border-line px-4 py-3" @if ($writing || $order->status === OrderStatus::AwaitingConsultant) open @endif>
            <summary class="min-h-touch cursor-pointer content-center text-label font-semibold text-ink">نمایش متن گزارش</summary>
            <div class="mt-4">{!! $report->html !!}</div>
        </details>
    @endif
</x-card>
