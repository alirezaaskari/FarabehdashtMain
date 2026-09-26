{{--
    صفحه اصلی: از کار روزانه کارشناس شروع می‌کند، نه از فهرست ماژول‌ها.

    قهرمان (جست‌وجو + تصویر متحرک)، محاسبه سریع، مسیرهای «امروز چه کاری
    داری؟»، بخش‌های ماژول‌ها، «سه قدم تا گزارش»، دعوت به نویسندگی و مشارکت،
    کاشی‌های کمک، نوار اشتراک و سلب مسئولیت.

    بخش‌های داده‌دار از `HomePage` یا از include خود ماژول‌ها می‌آیند، نه از این
    قالب: با خاموش‌شدن یک ماژول، بخشش خودش می‌رود و صفحه نمی‌شکند (قاعده ۲).
    هر `route()` این‌جا با `Route::has` محافظت می‌شود. هیچ پرس‌وجویی این‌جا نیست
    (قاعده ۴).
--}}

@use('App\Support\Home\HomeLayout')

@php
    $trust = [
        ['check', 'هر مقاله بازبین علمی و تاریخ بازبینی دارد', 'text-primary'],
        ['book', 'هر فرمول با منبع و نسخه مشخص', 'text-primary'],
        ['alert', 'بدون ادعای تشخیص پزشکی یا انطباق قطعی', 'text-caution'],
    ];

    // جست‌وجوهای پیشنهادی قهرمان؛ نتیجه‌شان را خود جست‌وجوی سراسری می‌دهد.
    $popular = [['بنزن', '71-43-2'], ['دز صدا', null], ['WBGT', null], ['بلندکردن بار NIOSH', null]];

    $tasks = array_values(array_filter([
        Route::has('tools.index') ? ['در محل اندازه می‌گیرم', 'ابزار میدانی با دکمه‌های درشت، حالت کار در کارگاه و جدول نقطه‌های همین جلسه.', 'ابزارها', route('tools.index')] : null,
        Route::has('chemicals.index') ? ['با حد مجاز مقایسه می‌کنم', 'حدود مواجهه چند مرجع کنار هم، با شماره CAS، مسیر مواجهه و روش نمونه‌برداری.', 'بانک مواد', route('chemicals.index')] : null,
        Route::has('reports.create') ? ['گزارش می‌نویسم', 'گزارش‌ساز چهارمرحله‌ای از نتیجه‌های ذخیره‌شده، خروجی PDF و کد بررسی اصالت.', 'گزارش‌ساز', route('reports.create')] : null,
        Route::has('encyclopedia.index') ? ['یاد می‌گیرم', 'مقاله‌های بازبینی‌شده، دوره‌های تخصصی و پرسش از متخصص تأییدشده.', 'دانشنامه', route('encyclopedia.index')] : null,
    ]));

    // راه‌های دیگر مشارکت؛ هرکدام فقط وقتی ماژولش روشن است.
    $roles = array_values(array_filter([
        Route::has('identity.profiles') && Route::has('courses.index') ? ['book', 'مدرس شو', 'دوره، جلسه و آزمون بساز و از فروشش درآمد داشته باش.', route('identity.profiles')] : null,
        Route::has('commerce.sell') ? ['file', 'فایل‌هایت را بفروش', 'فرم، چک‌لیست و قالب گزارش منتشر کن؛ تسویه هفتگی به حساب بانکی.', route('commerce.sell')] : null,
        Route::has('identity.profiles') && Route::has('expert.index') ? ['info', 'به پرسش‌ها پاسخ بده', 'مشاور تأییدشده شو و به پرسش تخصصی همکارانت پاسخ بده.', route('identity.profiles')] : null,
    ]));

    $sectionRows = array_values(array_filter($rows, static fn (array $row): bool => $row[0]->layout !== HomeLayout::Tile));
    $tileRows = array_values(array_filter($rows, static fn (array $row): bool => $row[0]->layout === HomeLayout::Tile));
@endphp

<x-layouts.public :seo="$seo" :padded="false">

    {{-- قهرمان --}}
    <section class="grid items-center gap-8 border-b border-line bg-surface px-6 pt-8 pb-10 md:px-gutter md:py-14
                    lg:grid-cols-[minmax(0,1fr)_minmax(0,28rem)] lg:gap-14">
        <div>
            <x-badge tone="primary" icon="check" class="hidden sm:inline-flex">میزکار فارسی بهداشت حرفه‌ای و ایمنی کار</x-badge>

            <h1 class="text-hero text-ink sm:mt-4.5">از اندازه‌گیری در کارگاه<br class="max-sm:hidden"> تا گزارش امضاشده.</h1>

            <p class="mt-5 max-w-[35rem] text-lede text-muted">
                عدد را وارد کن، با منبع علمی محاسبه‌اش کن، با حد مجاز مرجع کنارش بگذار و گزارشی بساز که هرکس
                بتواند اصالتش را بررسی کند.
            </p>

            @if (Route::has('workspace.search'))
                <form method="GET" action="{{ route('workspace.search') }}" role="search" class="mt-7 flex max-w-[37.5rem] gap-2.5">
                    <label for="home-search" class="sr-only">جست‌وجو در ابزارها، مواد و دانشنامه</label>
                    <input id="home-search" type="search" name="q" placeholder="نام ماده، شماره CAS، ابزار یا موضوع…"
                           class="h-field-lg min-w-0 grow rounded-lg border border-line-strong bg-surface px-4 text-control text-ink
                                  placeholder:text-muted">
                    <x-button type="submit" size="lg" icon="search" class="shrink-0 max-sm:px-4">
                        <span class="max-sm:sr-only">جست‌وجو</span>
                    </x-button>
                </form>

                <div class="mt-3.5 flex flex-wrap items-center gap-2">
                    <span class="text-note text-muted">پرجست‌وجو:</span>
                    @foreach ($popular as [$term, $code])
                        <a href="{{ route('workspace.search', ['q' => $term]) }}"
                           class="inline-flex min-h-touch items-center gap-1.5 rounded-full border border-line px-3.5 text-note font-semibold
                                  text-body no-underline hover:border-primary-line hover:text-primary hover:no-underline">
                            {{ $term }}
                            @if ($code)
                                <span class="text-muted" data-numeric>{{ $code }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                @if (Route::has('tools.index'))
                    <div class="mt-7">
                        <x-button :href="route('tools.index')" size="lg" class="max-sm:w-full">شروع با ابزارها</x-button>
                    </div>
                @endif
            @endif
        </div>

        <x-illustration.measure-scene class="mx-auto w-full max-w-[28rem] max-lg:max-w-[22rem]" />
    </section>

    @includeIf('tools::home.quick-convert')

    <section class="border-b border-line px-6 py-5 md:px-gutter">
        <ul class="flex list-none flex-col gap-3 ps-0 md:flex-row md:gap-12">
            @foreach ($trust as [$icon, $text, $iconClass])
                <li class="flex items-center gap-2.5 text-label font-semibold text-body">
                    <span class="{{ $iconClass }}"><x-icon :name="$icon" :size="18" /></span>
                    {{ $text }}
                </li>
            @endforeach
        </ul>
    </section>

    @if ($tasks !== [])
        <section aria-labelledby="home-tasks" class="border-b border-line px-6 py-14 md:px-gutter">
            <h2 id="home-tasks" class="text-h1 text-ink">امروز چه کاری داری؟</h2>
            <p class="mt-2.5 text-copy text-muted">از مرحله‌ای که در آن هستی شروع کن؛ بقیه راه یک کلیک فاصله دارد.</p>

            <ul class="mt-8 grid list-none grid-cols-2 gap-3 ps-0 md:gap-5 lg:grid-cols-4">
                @foreach ($tasks as $index => [$title, $text, $cta, $url])
                    <li>
                        <a href="{{ $url }}"
                           class="flex h-full flex-col gap-2.5 rounded-xl border border-line bg-surface p-4.5 no-underline
                                  hover:border-primary-line hover:no-underline md:p-6">
                            <span class="hidden text-note font-bold text-primary md:block">@fa($index + 1)</span>
                            <h3 class="text-copy font-bold text-ink md:text-h3">{{ $title }}</h3>
                            <p class="hidden text-note text-muted md:block">{{ $text }}</p>
                            <span class="mt-auto pt-1 text-label font-bold text-primary">{{ $cta }} ←</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @foreach ($sectionRows as $row)
        @if ($row[0]->layout->isHalf() && count($row) === 2)
            <div class="grid border-b border-line px-6 py-14 md:px-gutter lg:grid-cols-2 lg:gap-6">
                @foreach ($row as $section)
                    @include('core::home.section', ['section' => $section, 'framed' => true])
                @endforeach
            </div>
        @else
            <div class="border-b border-line bg-surface px-6 py-14 md:px-gutter">
                @include('core::home.section', ['section' => $row[0], 'framed' => $row[0]->layout->isHalf()])
            </div>
        @endif
    @endforeach

    @includeIf('reports::home.workflow')

    {{-- دعوت به نویسندگی: هر کارشناس می‌تواند محتوا وارد سایت کند، پس از تأیید مدیر. --}}
    @if (Route::has('encyclopedia.writing-guide'))
        <section aria-labelledby="home-contribute" class="px-6 py-14 md:px-gutter">
            <div class="grid gap-8 rounded-xl border border-primary-line bg-primary-soft p-6 md:p-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-12">
                <div>
                    <span class="text-label font-bold text-primary">فرابهداشت را با هم می‌سازیم</span>
                    <h2 id="home-contribute" class="mt-2 text-h1 text-ink">دانسته‌ات را بنویس، با نام خودت منتشر کن.</h2>
                    <p class="mt-3 text-copy text-body">
                        هر کارشناس بهداشت حرفه‌ای می‌تواند نویسنده دانشنامه شود. مقاله‌ات پیش از انتشار بازبینی علمی
                        می‌شود و با نام و صفحه نویسنده خودت منتشر می‌شود.
                    </p>

                    <ol class="mt-6 list-none space-y-3 ps-0">
                        @foreach (['پروفایل «نویسنده دانشنامه» را در حسابت فعال کن', 'پیش‌نویس مقاله یا راهنما را در میزکار بنویس', 'بازبینی علمی و تأیید مدیر', 'انتشار با نام تو و پیوند به صفحه نویسنده'] as $index => $step)
                            <li class="flex items-center gap-3 text-copy text-ink">
                                <span class="flex size-7.5 shrink-0 items-center justify-center rounded-full bg-primary text-note font-bold text-on-primary">@fa($index + 1)</span>
                                {{ $step }}
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-7 flex flex-wrap gap-3">
                        @if (Route::has('identity.profiles'))
                            <x-button :href="route('identity.profiles')" size="lg" class="max-sm:w-full">نویسنده شو</x-button>
                        @endif
                        <x-button :href="route('encyclopedia.writing-guide')" variant="secondary" size="lg" class="max-sm:w-full">راهنمای نوشتن</x-button>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <x-illustration.writer class="mx-auto max-w-[22rem]" />

                    @if ($roles !== [])
                        <h3 class="mt-2 text-label font-bold text-muted">راه‌های دیگر مشارکت</h3>
                        <ul class="list-none space-y-2.5 ps-0">
                            @foreach ($roles as [$icon, $title, $text, $url])
                                <li>
                                    <a href="{{ $url }}"
                                       class="flex items-center gap-4 rounded-note border border-line bg-surface p-4 no-underline
                                              hover:border-primary-line hover:no-underline">
                                        <span class="flex size-11 shrink-0 items-center justify-center rounded-md bg-primary-soft text-primary">
                                            <x-icon :name="$icon" :size="20" />
                                        </span>
                                        <span class="grow">
                                            <span class="block text-h4 text-ink">{{ $title }}</span>
                                            <span class="block text-note text-muted">{{ $text }}</span>
                                        </span>
                                        <span class="text-primary" aria-hidden="true">←</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <p class="text-note text-muted">هر محتوایی که کاربران می‌فرستند پیش از انتشار با تأیید مدیر منتشر می‌شود.</p>
                </div>
            </div>
        </section>
    @endif

    @if ($tileRows !== [])
        <section aria-labelledby="home-help" class="border-y border-line bg-surface px-6 py-14 md:px-gutter">
            <h2 id="home-help" class="text-h1 text-ink">وقتی کمک بیشتری لازم است</h2>
            @foreach ($tileRows as $row)
                <div class="mt-8 grid gap-5 md:grid-cols-3">
                    @foreach ($row as $section)
                        @include('core::home.tile', ['section' => $section])
                    @endforeach
                </div>
            @endforeach
        </section>
    @endif

    @includeIf('monetization::home.pro-band')

    <section class="px-6 pb-10 md:px-gutter">
        <x-disclaimer size="lg">
            خروجی ابزارها و محتوای فرابهداشت جنبه آموزشی و کمک‌کارشناسی دارد و هیچ‌کدام ادعای تشخیص
            پزشکی، تأیید ایمنی قطعی یا انطباق قانونی قطعی ندارند. گواهی و نشان‌های داخلی سایت مدرک
            رسمی یا مجوز حرفه‌ای محسوب نمی‌شوند.
        </x-disclaimer>
    </section>
</x-layouts.public>
