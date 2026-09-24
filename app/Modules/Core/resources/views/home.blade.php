{{--
    صفحه اصلی — همان چیدمان پروتوتایپ: قهرمان، نوار اعتماد، بخش‌های ماژول‌ها،
    سلب مسئولیت.

    بخش‌ها از `HomePage` می‌آیند و نه از این قالب: با خاموش‌شدن یک ماژول، بخشش
    خودش می‌رود و صفحه نمی‌شکند (قاعده ۲). هیچ پرس‌وجویی اینجا نیست (قاعده ۴).
--}}

@php
    $trust = [
        ['check', 'هر محتوا بازبین علمی و تاریخ بازبینی دارد', 'text-focus'],
        ['book', 'هر فرمول با منبع و نسخه مشخص', 'text-focus'],
        ['alert', 'بدون ادعای تشخیص پزشکی یا انطباق قطعی', 'text-caution-line'],
    ];
@endphp

<x-layouts.public :seo="$seo" :padded="false">

    <section class="grid items-center gap-10 border-b border-line bg-surface px-6 py-10 md:px-gutter md:py-16
                    lg:grid-cols-[minmax(0,1fr)_minmax(0,27rem)] lg:gap-14">
        <div>
            <x-badge tone="primary" icon="check" class="hidden sm:inline-flex">بهداشت حرفه‌ای و ایمنی کار</x-badge>

            <h1 class="text-hero text-ink sm:mt-4.5">
                یاد بگیر، محاسبه کن،<br>مستند بساز.
            </h1>

            <p class="mt-5 max-w-[32.5rem] text-lede text-muted">
                میزکار فارسی متخصص بهداشت حرفه‌ای: دانشنامه بازبینی‌شده، ابزارهای محاسباتی با منبع علمی،
                بانک مواد شیمیایی، و فایل‌ها و دوره‌های تخصصی — همه در یک حساب.
            </p>

            {{-- روی گوشی جست‌وجو اولین کار است؛ روی دسکتاپ کادرش در سربرگ هست. --}}
            @if (Route::has('workspace.search'))
                <form method="GET" action="{{ route('workspace.search') }}" role="search" class="mt-6 lg:hidden">
                    <label for="home-search" class="sr-only">جست‌وجو در سایت</label>
                    <input id="home-search" type="search" name="q" placeholder="جست‌وجو در دانشنامه و مواد…"
                           class="h-field-lg w-full rounded-lg border border-line-strong bg-surface px-4 text-control text-ink
                                  placeholder:text-muted">
                </form>
            @endif

            <div class="mt-6 flex flex-wrap gap-3.5 md:mt-8">
                @if (Route::has('tools.index'))
                    <x-button :href="route('tools.index')" variant="primary" size="lg" class="max-sm:w-full">شروع با ابزارها</x-button>
                @endif

                @if (Route::has('encyclopedia.index'))
                    <x-button :href="route('encyclopedia.index')" variant="secondary" size="lg" class="max-sm:hidden">ورود به دانشنامه</x-button>
                @endif
            </div>
        </div>

        {{-- فرم محاسبه سریع مال ماژول ابزارهاست؛ اگر خاموش باشد، نمایش داده نمی‌شود. --}}
        <div class="hidden lg:block">
            @includeIf('tools::home.quick-convert')
        </div>
    </section>

    {{--
        نوار اعتماد: تنها جای صفحه که پس‌زمینه تیره دارد، مثل پروتوتایپ.
        آیکون هشدار از `caution-line` رنگ می‌گیرد و نه `caution`: رنگ هشدار تیره است
        و روی پس‌زمینه ink کنتراست لازم را ندارد.
    --}}
    <section class="bg-ink px-6 py-6 md:px-gutter">
        <ul class="flex list-none flex-col gap-4 ps-0 md:flex-row md:gap-12">
            @foreach ($trust as [$icon, $text, $iconClass])
                <li class="flex items-center gap-2.5 text-label font-semibold text-on-primary">
                    <span class="{{ $iconClass }}"><x-icon :name="$icon" :size="19" /></span>
                    {{ $text }}
                </li>
            @endforeach
        </ul>
    </section>

    @foreach ($rows as $row)
        @if (count($row) === 2)
            <div class="grid border-b border-line px-6 py-14 md:px-gutter lg:grid-cols-2 lg:gap-6">
                @foreach ($row as $section)
                    @include('core::home.section', ['section' => $section, 'framed' => true])
                @endforeach
            </div>
        @else
            <div class="border-b border-line px-6 py-14 md:px-gutter">
                @include('core::home.section', ['section' => $row[0], 'framed' => $row[0]->layout->isHalf()])
            </div>
        @endif
    @endforeach

    <section class="px-6 py-10 md:px-gutter">
        <x-disclaimer size="lg">
            خروجی ابزارها و محتوای فرابهداشت جنبه آموزشی و کمک‌کارشناسی دارد و هیچ‌کدام ادعای تشخیص
            پزشکی، تأیید ایمنی قطعی یا انطباق قانونی قطعی ندارند. گواهی و نشان‌های داخلی سایت مدرک
            رسمی یا مجوز حرفه‌ای محسوب نمی‌شوند.
        </x-disclaimer>
    </section>
</x-layouts.public>
