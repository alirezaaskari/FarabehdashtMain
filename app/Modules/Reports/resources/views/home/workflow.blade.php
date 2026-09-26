{{--
    «یک اندازه‌گیری، سه قدم تا گزارش» در صفحه اصلی.

    مال ماژول گزارش‌هاست چون وعده آخرش گزارش است؛ با خاموش‌شدن این ماژول
    بخش هم نیست. `$singlePrice` را composer همین ماژول می‌دهد و فقط وقتی
    فروش تک‌گزارش باز است عدد دارد. عددهای مثال نمونه‌اند، نه اندازه‌گیری.
--}}

@php
    $steps = [
        ['measure', 'عددها را وارد کن', 'تراز معادل هر کار و مدتش؛ مثلاً ۸۸ دسی‌بل در ۶ ساعت کنار پرس.'],
        ['result', 'نتیجه را با تفسیرش ببین', 'تراز مواجهه روزانه با منبع و نسخه فرمول، و آنچه مقایسه با حد مرجع به آن بستگی دارد.'],
        ['report', 'گزارش قابل بررسی بساز', 'نتیجه‌های ذخیره‌شده در گزارش‌ساز چهارمرحله‌ای، خروجی PDF فارسی و کد بررسی اصالت.'],
    ];
@endphp

<section aria-labelledby="home-workflow" class="border-b border-line px-6 py-14 md:px-gutter">
    <h2 id="home-workflow" class="text-h1 text-ink">یک اندازه‌گیری، سه قدم تا گزارش</h2>
    <p class="mt-2.5 text-copy text-muted">نتیجه‌ها در میزکار و پروژه‌ات می‌مانند و هر وقت خواستی گزارششان را می‌سازی.</p>

    <ol class="mt-8 grid list-none gap-5 ps-0 md:grid-cols-3">
        @foreach ($steps as $index => [$kind, $title, $text])
            <li class="flex flex-col gap-4 rounded-xl border border-line bg-surface p-5">
                <x-illustration.step :kind="$kind" />
                <h3 class="flex items-center gap-2.5 text-h4 text-ink">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-ink text-note font-bold text-on-primary">@fa($index + 1)</span>
                    {{ $title }}
                </h3>
                <p class="text-note text-muted">{{ $text }}</p>
            </li>
        @endforeach
    </ol>

    <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3">
        @if (Route::has('tools.show'))
            <x-button :href="route('tools.show', 'daily-noise-exposure')" size="lg" class="max-sm:w-full">امتحانش کن</x-button>
        @endif

        <p class="text-copy text-muted">
            صدور گزارش با اشتراک حرفه‌ای{{ $singlePrice === null ? '' : ' یا خرید تکی '.$singlePrice->format() }}.
        </p>
    </div>
</section>
