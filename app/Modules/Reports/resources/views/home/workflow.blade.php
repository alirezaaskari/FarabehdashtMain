{{--
    «یک اندازه‌گیری، سه قدم تا گزارش» در صفحه اصلی.

    مال ماژول گزارش‌هاست چون وعده آخرش گزارش است؛ با خاموش‌شدن این ماژول
    بخش هم نیست. `$singlePrice` را composer همین ماژول می‌دهد و فقط وقتی
    فروش تک‌گزارش باز است عدد دارد. عددهای مثال نمونه‌اند، نه اندازه‌گیری.
--}}

@php
    $steps = [
        ['home-step-input', 'عددها را وارد کن', 'تراز معادل هر کار و مدتش؛ مثلاً ۸۸ دسی‌بل در ۶ ساعت کنار پرس.'],
        ['home-step-result', 'نتیجه را با تفسیرش ببین', 'تراز مواجهه روزانه با منبع و نسخه فرمول، و آنچه مقایسه با حد مرجع به آن بستگی دارد.'],
        ['home-step-report', 'گزارش قابل بررسی بساز', 'نتیجه‌های ذخیره‌شده در گزارش‌ساز چهارمرحله‌ای، خروجی PDF فارسی و کد بررسی اصالت.'],
    ];
@endphp

<section aria-labelledby="home-workflow" class="border-b border-line px-6 py-16 md:px-gutter md:py-20">
    <div class="grid gap-10 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:gap-16">
        <div>
            <x-art name="home-workflow" class="mb-6 h-28 w-auto" />
            <h2 id="home-workflow" class="text-h2 text-ink">یک اندازه‌گیری، سه قدم تا گزارش</h2>
            <p class="mt-2 text-copy text-muted">نتیجه‌ها در میزکار و پروژه‌ات می‌مانند و هر وقت خواستی گزارششان را می‌سازی.</p>

            <div class="mt-7 flex flex-col items-start gap-3">
                @if (Route::has('tools.show'))
                    <x-button :href="route('tools.show', 'daily-noise-exposure')" size="lg" class="max-sm:w-full">امتحانش کن</x-button>
                @endif

                <p class="text-note text-muted">
                    صدور گزارش با اشتراک حرفه‌ای{{ $singlePrice === null ? '' : ' یا خرید تکی '.$singlePrice->format() }}.
                </p>
            </div>
        </div>

        {{-- ترتیب قدم‌ها خودش اطلاعات است، پس شماره دارند. --}}
        <ol class="grid list-none gap-8 ps-0 md:grid-cols-3 md:gap-6">
            @foreach ($steps as $index => [$art, $title, $text])
                <li class="border-t-2 border-ink pt-5">
                    <x-art :name="$art" class="mb-4 h-20 w-auto" />
                    <span class="text-note font-semibold text-muted">قدم @fa($index + 1)</span>
                    <h3 class="mt-1 text-h4 text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-note text-muted">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
