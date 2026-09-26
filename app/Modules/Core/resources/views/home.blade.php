{{--
    صفحه اصلی: از کار روزانه کارشناس شروع می‌کند، نه از فهرست ماژول‌ها.

    قهرمان (جست‌وجو و محاسبه سریع زنده)، مسیرهای «امروز چه کاری داری؟»،
    بخش‌های ماژول‌ها، «سه قدم تا گزارش»، دعوت به نویسندگی و مشارکت، کمک
    بیشتر، اشتراک و سلب مسئولیت. بی‌قاب اضافه: خط نازک جداکننده به‌جای کارت.
    تصویرها خطی و سیاه‌وسفیدند (x-art): شخصیت کارشناس و صحنه‌های ساده.

    بخش‌های داده‌دار از `HomePage` یا از include خود ماژول‌ها می‌آیند، نه از این
    قالب: با خاموش‌شدن یک ماژول، بخشش خودش می‌رود و صفحه نمی‌شکند (قاعده ۲).
    هر `route()` این‌جا با `Route::has` محافظت می‌شود. هیچ پرس‌وجویی این‌جا نیست
    (قاعده ۴).
--}}

@use('App\Support\Home\HomeLayout')

@php
    $trust = [
        'هر مقاله بازبین علمی و تاریخ بازبینی دارد',
        'هر فرمول با منبع و نسخه مشخص',
        'بدون ادعای تشخیص یا انطباق قطعی',
    ];

    // جست‌وجوهای پیشنهادی قهرمان؛ نتیجه‌شان را خود جست‌وجوی سراسری می‌دهد.
    $popular = [['بنزن', '71-43-2'], ['دز صدا', null], ['WBGT', null], ['بلندکردن بار NIOSH', null]];

    $tasks = array_values(array_filter([
        Route::has('tools.index') ? ['character.measure', 'در محل اندازه می‌گیرم', 'ابزار میدانی با دکمه‌های درشت، حالت کار در کارگاه و جدول نقطه‌های همین جلسه.', 'ابزارها', route('tools.index')] : null,
        Route::has('chemicals.index') ? ['character.chemical', 'با حد مجاز مقایسه می‌کنم', 'حدود مواجهه چند مرجع کنار هم، با شماره CAS، مسیر مواجهه و روش نمونه‌برداری.', 'بانک مواد', route('chemicals.index')] : null,
        Route::has('reports.create') ? ['character.report', 'گزارش می‌نویسم', 'گزارش‌ساز چهارمرحله‌ای از نتیجه‌های ذخیره‌شده، خروجی PDF و کد بررسی اصالت.', 'گزارش‌ساز', route('reports.create')] : null,
        Route::has('encyclopedia.index') ? ['character.read', 'یاد می‌گیرم', 'مقاله‌های بازبینی‌شده، دوره‌های تخصصی و پرسش از متخصص تأییدشده.', 'دانشنامه', route('encyclopedia.index')] : null,
    ]));

    // راه‌های دیگر مشارکت؛ هرکدام فقط وقتی ماژولش روشن است.
    $roles = array_values(array_filter([
        Route::has('identity.profiles') && Route::has('courses.index') ? ['مدرس شو', 'دوره، جلسه و آزمون بساز و از فروشش درآمد داشته باش.', route('identity.profiles')] : null,
        Route::has('commerce.sell') ? ['فایل‌هایت را بفروش', 'فرم، چک‌لیست و قالب گزارش منتشر کن؛ تسویه هفتگی به حساب بانکی.', route('commerce.sell')] : null,
        Route::has('identity.profiles') && Route::has('expert.index') ? ['به پرسش‌ها پاسخ بده', 'مشاور تأییدشده شو و به پرسش تخصصی همکارانت پاسخ بده.', route('identity.profiles')] : null,
    ]));

    $sectionRows = array_values(array_filter($rows, static fn (array $row): bool => $row[0]->layout !== HomeLayout::Tile));
    $tileRows = array_values(array_filter($rows, static fn (array $row): bool => $row[0]->layout === HomeLayout::Tile));
@endphp

<x-layouts.public :seo="$seo" :padded="false">

    {{-- قهرمان: پیام و جست‌وجو در یک سو، محاسبه زنده در سوی دیگر. --}}
    <section class="border-b border-line px-6 pt-10 pb-12 md:px-gutter md:pt-16 md:pb-20">
        <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,30rem)] lg:gap-20">
            <div class="lg:pt-6">
                <h1 class="max-w-[16ch] text-hero text-ink">از اندازه‌گیری در کارگاه تا گزارش امضاشده.</h1>

                <p class="mt-6 max-w-[34rem] text-lede text-muted">
                    عدد را وارد کن، با منبع علمی محاسبه‌اش کن، با حد مجاز مرجع کنارش بگذار و گزارشی بساز که هرکس
                    بتواند اصالتش را بررسی کند.
                </p>

                @if (Route::has('workspace.search'))
                    <form method="GET" action="{{ route('workspace.search') }}" role="search" class="mt-9 flex max-w-[36rem] gap-2">
                        <label for="home-search" class="sr-only">جست‌وجو در ابزارها، مواد و دانشنامه</label>
                        <input id="home-search" type="search" name="q" placeholder="نام ماده، شماره CAS، ابزار یا موضوع…"
                               class="h-field-lg min-w-0 grow rounded-md border border-line-strong bg-surface px-4 text-control text-ink
                                      placeholder:text-dim">
                        <x-button type="submit" size="lg" icon="search" class="shrink-0 max-sm:px-4">
                            <span class="max-sm:sr-only">جست‌وجو</span>
                        </x-button>
                    </form>

                    <p class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-note text-muted">
                        <span>پرجست‌وجو:</span>
                        @foreach ($popular as [$term, $code])
                            <a href="{{ route('workspace.search', ['q' => $term]) }}"
                               class="inline-flex min-h-touch items-center gap-1.5 font-medium text-body underline decoration-line-strong
                                      underline-offset-4 hover:text-primary hover:decoration-primary">
                                {{ $term }}
                                @if ($code)
                                    <span class="text-muted" data-numeric>{{ $code }}</span>
                                @endif
                            </a>
                        @endforeach
                    </p>
                @elseif (Route::has('tools.index'))
                    <div class="mt-9">
                        <x-button :href="route('tools.index')" size="lg" class="max-sm:w-full">شروع با ابزارها</x-button>
                    </div>
                @endif

                <ul class="mt-12 grid list-none gap-3 border-t border-line ps-0 pt-6 text-note text-muted sm:grid-cols-3 sm:gap-6">
                    @foreach ($trust as $text)
                        <li class="flex items-start gap-2">
                            <span class="mt-1 shrink-0 text-primary"><x-icon name="check" :size="15" :stroke="2.5" /></span>
                            {{ $text }}
                        </li>
                    @endforeach
                </ul>
            </div>

            @includeIf('tools::home.quick-convert')
        </div>

        {{-- صحنه کارگاه: کارخانه و کارشناس‌ها روی یک خط زمین به پهنای قهرمان.
             روی موبایل صحنه کوتاه‌تر (فقط کارخانه و دو کارشناس) می‌آید. --}}
        <x-art name="scene.yard" class="mt-14 hidden h-auto w-full md:block" />
        <x-art name="scene.site" class="mt-10 h-auto w-full md:hidden" />
    </section>

    @if ($tasks !== [])
        <section aria-labelledby="home-tasks" class="border-b border-line px-6 py-16 md:px-gutter md:py-20">
            <h2 id="home-tasks" class="text-h2 text-ink">امروز چه کاری داری؟</h2>

            <ul class="mt-8 grid list-none grid-cols-2 gap-px overflow-hidden rounded-xl border border-line bg-line ps-0 lg:grid-cols-4">
                @foreach ($tasks as [$art, $title, $text, $cta, $url])
                    <li class="bg-surface">
                        <a href="{{ $url }}" class="group flex h-full flex-col gap-2 p-4.5 no-underline hover:bg-surface-2 hover:no-underline md:p-6">
                            <x-art :name="$art" class="mb-2 h-24 w-auto self-start md:h-32" />
                            <h3 class="text-copy font-semibold text-ink md:text-h4">{{ $title }}</h3>
                            <p class="hidden text-note text-muted md:block">{{ $text }}</p>
                            <span class="mt-auto inline-flex items-center gap-1.5 pt-3 text-label font-semibold text-primary">
                                {{ $cta }}
                                <span aria-hidden="true" class="transition-transform group-hover:-translate-x-1">←</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @foreach ($sectionRows as $row)
        @if ($row[0]->layout->isHalf() && count($row) === 2)
            <div class="grid gap-12 border-b border-line px-6 py-16 md:px-gutter md:py-20 lg:grid-cols-2 lg:gap-16">
                @foreach ($row as $section)
                    @include('core::home.section', ['section' => $section])
                @endforeach
            </div>
        @else
            <div class="border-b border-line px-6 py-16 md:px-gutter md:py-20">
                @include('core::home.section', ['section' => $row[0]])
            </div>
        @endif
    @endforeach

    @includeIf('reports::home.workflow')

    {{-- دعوت به نویسندگی: هر کارشناس می‌تواند محتوا وارد سایت کند، پس از تأیید مدیر. --}}
    @if (Route::has('encyclopedia.writing-guide'))
        <section aria-labelledby="home-contribute" class="border-b border-line bg-surface-2 px-6 py-16 md:px-gutter md:py-20">
            <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <x-art name="character.writer" class="mb-6 h-32 w-auto" />
                    <h2 id="home-contribute" class="text-h1 text-ink">دانسته‌ات را بنویس، با نام خودت منتشر کن.</h2>
                    <p class="mt-4 max-w-[36rem] text-copy text-body">
                        هر کارشناس بهداشت حرفه‌ای می‌تواند نویسنده دانشنامه شود. مقاله‌ات پیش از انتشار بازبینی علمی
                        می‌شود و با نام و صفحه نویسنده خودت منتشر می‌شود.
                    </p>

                    <ol class="mt-8 list-none space-y-4 ps-0">
                        @foreach (['پروفایل «نویسنده دانشنامه» را در حسابت فعال کن', 'پیش‌نویس مقاله یا راهنما را در میزکار بنویس', 'بازبینی علمی و تأیید مدیر', 'انتشار با نام تو و پیوند به صفحه نویسنده'] as $index => $step)
                            <li class="flex items-center gap-4 text-copy text-ink">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full border border-line-strong bg-surface text-note font-semibold text-body">@fa($index + 1)</span>
                                {{ $step }}
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-9 flex flex-wrap gap-3">
                        @if (Route::has('identity.profiles'))
                            <x-button :href="route('identity.profiles')" size="lg" class="max-sm:w-full">نویسنده شو</x-button>
                        @endif
                        <x-button :href="route('encyclopedia.writing-guide')" variant="secondary" size="lg" class="max-sm:w-full">راهنمای نوشتن</x-button>
                    </div>
                </div>

                @if ($roles !== [])
                    <div>
                        <h3 class="text-label font-semibold text-muted">راه‌های دیگر مشارکت</h3>
                        <ul class="mt-3 list-none border-t border-line-strong ps-0">
                            @foreach ($roles as [$title, $text, $url])
                                <li class="border-b border-line">
                                    <a href="{{ $url }}" class="group flex items-center gap-4 py-5 no-underline hover:no-underline">
                                        <span class="grow">
                                            <span class="block text-h4 text-ink group-hover:text-primary">{{ $title }}</span>
                                            <span class="mt-0.5 block text-note text-muted">{{ $text }}</span>
                                        </span>
                                        <span class="text-muted transition-transform group-hover:-translate-x-1 group-hover:text-primary" aria-hidden="true">←</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-4 text-note text-muted">هر محتوایی که کاربران می‌فرستند پیش از انتشار با تأیید مدیر منتشر می‌شود.</p>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($tileRows !== [])
        <section aria-labelledby="home-help" class="border-b border-line px-6 py-16 md:px-gutter md:py-20">
            <h2 id="home-help" class="text-h2 text-ink">وقتی کمک بیشتری لازم است</h2>
            @foreach ($tileRows as $row)
                <div class="mt-8 grid gap-x-10 gap-y-8 md:grid-cols-3">
                    @foreach ($row as $section)
                        @include('core::home.tile', ['section' => $section])
                    @endforeach
                </div>
            @endforeach
        </section>
    @endif

    @includeIf('monetization::home.pro-band')

    <section class="px-6 py-10 md:px-gutter">
        <x-disclaimer>
            خروجی ابزارها و محتوای فرابهداشت جنبه آموزشی و کمک‌کارشناسی دارد و هیچ‌کدام ادعای تشخیص
            پزشکی، تأیید ایمنی قطعی یا انطباق قانونی قطعی ندارند. گواهی و نشان‌های داخلی سایت مدرک
            رسمی یا مجوز حرفه‌ای محسوب نمی‌شوند.
        </x-disclaimer>
    </section>
</x-layouts.public>
