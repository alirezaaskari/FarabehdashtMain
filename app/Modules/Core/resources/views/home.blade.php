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

<x-layouts.public title="میزکار متخصص بهداشت حرفه‌ای"
                  description="دانشنامه بازبینی‌شده، ابزارهای محاسباتی با منبع علمی، بانک مواد شیمیایی، و فایل‌ها و دوره‌های تخصصی بهداشت حرفه‌ای و ایمنی کار."
                  :padded="false">

    <section class="border-b border-line bg-surface px-6 py-14 md:px-gutter md:py-16">
        <x-badge tone="primary" icon="check">بهداشت حرفه‌ای و ایمنی کار</x-badge>

        <h1 class="mt-4.5 text-h1 text-ink md:text-hero">
            یاد بگیر، محاسبه کن،<br>مستند بساز.
        </h1>

        <p class="mt-5 max-w-[32.5rem] text-lede text-muted">
            میزکار فارسی متخصص بهداشت حرفه‌ای: دانشنامه بازبینی‌شده، ابزارهای محاسباتی با منبع علمی،
            بانک مواد شیمیایی، و فایل‌ها و دوره‌های تخصصی — همه در یک حساب.
        </p>

        <div class="mt-8 flex flex-wrap gap-3.5">
            @if (Route::has('tools.index'))
                <x-button :href="route('tools.index')" variant="primary" size="lg">شروع با ابزارها</x-button>
            @endif

            @if (Route::has('encyclopedia.index'))
                <x-button :href="route('encyclopedia.index')" variant="secondary" size="lg">ورود به دانشنامه</x-button>
            @endif
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

    @foreach ($sections as $section)
        <section class="border-b border-line px-6 py-14 md:px-gutter" aria-labelledby="home-{{ $section->key }}">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="home-{{ $section->key }}" class="text-h1 text-ink">{{ $section->title }}</h2>
                    <p class="mt-2.5 max-w-[46rem] text-copy text-muted">{{ $section->lede }}</p>
                </div>

                @if ($section->moreUrl && $section->moreLabel)
                    <a href="{{ $section->moreUrl }}"
                       class="inline-flex min-h-touch items-center text-copy font-bold">
                        {{ $section->moreLabel }} ←
                    </a>
                @endif
            </div>

            @if ($section->layout === \App\Support\Home\HomeLayout::Chips)
                <ul class="mt-7 flex list-none flex-wrap gap-3 ps-0">
                    @foreach ($section->items as $item)
                        <li>
                            <a href="{{ $item->url }}"
                               class="flex min-h-touch flex-col justify-center rounded-lg border border-line
                                      bg-surface px-4 py-2 no-underline hover:border-primary-line hover:no-underline">
                                <span class="text-copy font-bold text-ink">{{ $item->title }}</span>
                                @if ($item->meta)
                                    <span class="text-note text-muted" data-numeric>{{ $item->meta }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <ul class="mt-7 grid list-none grid-cols-1 gap-5 ps-0 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($section->items as $item)
                        <li>
                            <a href="{{ $item->url }}"
                               class="flex h-full flex-col rounded-note border border-line bg-surface p-6
                                      no-underline hover:border-primary-line hover:no-underline">
                                @if ($item->kicker !== '')
                                    <span class="text-note font-bold text-primary">{{ $item->kicker }}</span>
                                @endif

                                <h3 class="mt-2 text-h4 text-ink">{{ $item->title }}</h3>

                                @if ($item->summary !== '')
                                    <p class="mt-2 text-note text-muted">{{ Str::limit($item->summary, 120) }}</p>
                                @endif

                                @if ($item->meta)
                                    <span class="mt-4 text-note font-semibold text-muted">{{ $item->meta }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach

    <section class="px-6 py-10 md:px-gutter">
        <x-disclaimer size="lg">
            خروجی ابزارها و محتوای فرابهداشت جنبه آموزشی و کمک‌کارشناسی دارد و هیچ‌کدام ادعای تشخیص
            پزشکی، تأیید ایمنی قطعی یا انطباق قانونی قطعی ندارند. گواهی و نشان‌های داخلی سایت مدرک
            رسمی یا مجوز حرفه‌ای محسوب نمی‌شوند.
        </x-disclaimer>
    </section>
</x-layouts.public>
