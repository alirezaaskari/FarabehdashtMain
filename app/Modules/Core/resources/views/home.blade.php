{{--
    صفحه اصلی: از کار روزانه کارشناس شروع می‌کند، نه از فهرست ماژول‌ها.

    قهرمان (جست‌وجو و محاسبه سریع زنده)، «امروز چه کاری داری؟»، بخش‌های
    ماژول‌ها، «سه قدم تا گزارش»، کاریابی، مشاوره و خدمات، یادگیری، مشارکت،
    اشتراک، پرسش‌های پرتکرار و سلب مسئولیت. بی‌قاب اضافه: خط نازک به‌جای کارت.

    ردیف‌ها جای خالی نمی‌گذارند: تعداد ستون‌های «یادگیری» از تعداد کاشی‌ها
    می‌آید.

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
        Route::has('tools.index') ? ['home-task-measure', 'در محل اندازه می‌گیرم', 'ابزار میدانی صدا، گرما، روشنایی و ارتعاش با دکمه‌های درشت و جدول نقطه‌های همین جلسه.', 'ابزارها', route('tools.index')] : null,
        Route::has('chemicals.index') ? ['home-task-chemical', 'با حد مجاز مقایسه می‌کنم', 'حدود مواجهه شغلی چند مرجع کنار هم، با شماره CAS، مسیر مواجهه و روش نمونه‌برداری.', 'بانک مواد', route('chemicals.index')] : null,
        Route::has('reports.create') ? ['home-task-report', 'گزارش می‌نویسم', 'گزارش‌ساز چهارمرحله‌ای از نتیجه‌های ذخیره‌شده، خروجی PDF فارسی و کد بررسی اصالت.', 'گزارش‌ساز', route('reports.create')] : null,
        Route::has('encyclopedia.index') ? ['home-task-read', 'یاد می‌گیرم', 'مقاله‌های بازبینی‌شده دانشنامه بهداشت حرفه‌ای، دوره‌های تخصصی و راهنماهای کاربردی.', 'دانشنامه', route('encyclopedia.index')] : null,
        Route::has('exam_prep.index') ? ['home-task-exam', 'برای آزمون آماده می‌شوم', 'بانک سؤال موضوعی، تمرین با پاسخ فوری، آزمون زمان‌دار و کارنامه نقاط ضعف.', 'آمادگی آزمون', route('exam_prep.index')] : null,
        Route::has('jobs.index') ? ['home-task-job', 'دنبال کار می‌گردم', 'آگهی‌های استخدام HSE و بهداشت حرفه‌ای، رایگان و بدون ورود؛ با هشدار شغل تازه.', 'کاریابی', route('jobs.index')] : null,
        Route::has('consulting.index') ? ['home-task-hire', 'مشاور می‌خواهم', 'مشاور تأییدشده پیدا کن، خدمت را سفارش بده و پرداخت تا تحویل کار امن بماند.', 'مشاوران', route('consulting.index')] : null,
        Route::has('webinars.index') ? ['home-task-event', 'در وبینار شرکت می‌کنم', 'رویدادها و وبینارهای زنده با زمان، مدرس و ظرفیت؛ ثبت‌نام رایگان یا پولی.', 'رویدادها', route('webinars.index')] : null,
    ]));

    // کاریابی: دو سوی بازار کار، هرکدام فقط وقتی مسیرش هست.
    $seekers = array_values(array_filter([
        Route::has('jobs.index') ? ['آگهی‌ها را رایگان ببین و درخواست بفرست', 'کارجو هیچ‌وقت برای دیدن آگهی یا فرستادن درخواست پول نمی‌دهد.', route('jobs.index')] : null,
        Route::has('jobs.passport.edit') ? ['گذرنامه مهارتی بساز', 'دوره‌ها، آزمون‌ها و مهارت‌هایت در یک صفحه، با میزان تطبیق هر آگهی.', route('jobs.passport.edit')] : null,
        Route::has('jobs.bank.index') ? ['اگر خواستی، در بانک رزومه باش', 'فقط با اجازه خودت؛ اطلاعات تماست بی‌اجازه تو به کارفرما نشان داده نمی‌شود.', route('jobs.bank.index')] : null,
    ]));

    $employers = array_values(array_filter([
        Route::has('jobs.employer.postings.create') ? ['آگهی استخدام بده', 'صفحه شرکت و آگهی پس از تأیید مدیر منتشر می‌شود.', route('jobs.employer.postings.create')] : null,
        Route::has('jobs.talent.index') ? ['در بانک رزومه جست‌وجو کن', 'کارشناس‌هایی که خودشان در دسترس بودن را اعلام کرده‌اند.', route('jobs.talent.index')] : null,
    ]));

    // مشاوره و خدمات تخصصی.
    $services = array_values(array_filter([
        Route::has('consulting.index') ? ['user', 'مشاوران بهداشت حرفه‌ای', 'صفحه هر مشاور با حوزه تخصص، استان، سابقه و خدمت‌هایش؛ سفارش آنلاین با پرداخت امانی.', route('consulting.index')] : null,
        Route::has('consulting.reviews.pick') ? ['file', 'بررسی گزارش توسط متخصص', 'گزارش ارزیابی یا اندازه‌گیری‌ات را پیش از تحویل، یک متخصص تأییدشده بازبینی می‌کند.', route('consulting.reviews.pick')] : null,
        Route::has('consulting.directory.index') ? ['compass', 'خدمات تخصصی و آزمایشگاه‌ها', 'دایرکتوری ارائه‌دهندگان خدمات بهداشت حرفه‌ای به تفکیک خدمت و شهر، با فرم درخواست تماس.', route('consulting.directory.index')] : null,
    ]));

    // یادگیری و رشد؛ کنار کاشی‌هایی که ماژول‌ها خودشان می‌فرستند.
    $grow = array_values(array_filter([
        Route::has('exam_prep.index') ? ['home-grow-exam', 'آمادگی آزمون', 'نمونه رایگان ده‌سؤالی، تمرین موضوعی و آزمون زمان‌دار با کارنامه.', 'بسته‌های آزمون', route('exam_prep.index')] : null,
        Route::has('webinars.index') ? ['home-grow-events', 'رویداد و وبینار', 'جلسه‌های زنده آموزشی با متخصصان؛ پیوند ورود فقط برای ثبت‌نام‌شده‌ها.', 'رویدادهای پیش رو', route('webinars.index')] : null,
        Route::has('bundles.index') ? ['home-grow-bundles', 'بسته‌های راه‌حل', 'فرم، دوره و ماه‌های اشتراک برای یک کار مشخص، با قیمتی کمتر از جمع اجزا.', 'بسته‌ها', route('bundles.index')] : null,
    ]));

    // راه‌های دیگر مشارکت؛ هرکدام فقط وقتی ماژولش روشن است.
    $roles = array_values(array_filter([
        Route::has('identity.profiles') && Route::has('courses.index') ? ['home-role-teach', 'مدرس شو', 'دوره، جلسه و آزمون بساز و از فروشش درآمد داشته باش.', route('identity.profiles')] : null,
        Route::has('commerce.sell') ? ['home-role-sell', 'فایل‌هایت را بفروش', 'فرم، چک‌لیست و قالب گزارش منتشر کن؛ تسویه هفتگی به حساب بانکی.', route('commerce.sell')] : null,
        Route::has('identity.profiles') && Route::has('expert.index') ? ['home-role-answer', 'به پرسش‌ها پاسخ بده', 'مشاور تأییدشده شو و به پرسش تخصصی همکارانت پاسخ بده.', route('identity.profiles')] : null,
        Route::has('identity.profiles') && Route::has('consulting.index') ? ['home-role-consult', 'خدماتت را عرضه کن', 'صفحه مشاور یا آزمایشگاه بساز و سفارش آنلاین بگیر.', route('identity.profiles')] : null,
        Route::has('identity.profiles') && Route::has('jobs.index') ? ['home-role-hire', 'کارفرما شو', 'صفحه شرکت بساز، آگهی بده و درخواست‌ها را یک‌جا ببین.', route('identity.profiles')] : null,
    ]));

    $sectionRows = array_values(array_filter($rows, static fn (array $row): bool => $row[0]->layout !== HomeLayout::Tile));
    $tiles = array_merge(...array_values(array_filter($rows, static fn (array $row): bool => $row[0]->layout === HomeLayout::Tile)) ?: [[]]);

    // ستون‌های «یادگیری» از تعداد کاشی‌ها: چهار تا یک ردیف چهارتایی، نه سه‌تا و یکی تنها.
    $helpCount = count($tiles) + count($grow);
    $helpColumns = match (true) {
        $helpCount % 4 === 0 => 'lg:grid-cols-4',
        $helpCount % 3 === 0 => 'lg:grid-cols-3',
        default => '',
    };
@endphp

<x-layouts.public :seo="$seo" :padded="false">

    {{-- قهرمان: پیام و جست‌وجو در یک سو، محاسبه زنده در سوی دیگر. --}}
    <section class="border-b border-line px-6 pt-10 pb-12 md:px-gutter md:pt-16 md:pb-20">
        <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,30rem)] lg:gap-20">
            <div class="lg:pt-6">
                <h1 class="max-w-[16ch] text-hero text-ink">از اندازه‌گیری در کارگاه تا گزارش امضاشده.</h1>

                <p class="mt-6 max-w-[36rem] text-lede text-muted">
                    فرابهداشت میزکار آنلاین بهداشت حرفه‌ای و ایمنی کار است: عدد را وارد کن، با منبع علمی
                    محاسبه‌اش کن، با حد مجاز مواجهه شغلی مقایسه کن و گزارشی بساز که هرکس بتواند اصالتش را
                    بررسی کند. آموزش، آمادگی آزمون، مشاوره و کاریابی HSE هم همین‌جاست.
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
        <x-art name="home-hero" class="mt-14 hidden h-auto w-full md:block" />
        <x-art name="home-hero-mobile" class="mt-10 h-auto w-full md:hidden" />
    </section>

    @if ($tasks !== [])
        <section aria-labelledby="home-tasks" class="border-b border-line px-6 py-16 md:px-gutter md:py-20">
            <h2 id="home-tasks" class="text-h2 text-ink">امروز چه کاری داری؟</h2>
            <p class="mt-2 max-w-[40rem] text-copy text-muted">هر کار روزانه کارشناس بهداشت حرفه‌ای یک نقطه شروع دارد؛ از همان‌جا وارد شو.</p>

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

    {{-- کاریابی: دو سوی بازار کار؛ کارجو هیچ‌وقت پول نمی‌دهد و بانک رزومه با اجازه خود اوست. --}}
    @if ($seekers !== [])
        <section aria-labelledby="home-career" class="border-b border-line bg-surface-2 px-6 py-16 md:px-gutter md:py-20">
            <div class="grid items-start gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <x-art name="home-career" class="mb-6 h-40 w-auto" />
                    <h2 id="home-career" class="text-h1 text-ink">کاریابی بهداشت حرفه‌ای و HSE</h2>
                    <p class="mt-4 max-w-[34rem] text-copy text-body">
                        آگهی‌های استخدام کارشناس بهداشت حرفه‌ای، ایمنی و محیط زیست را بدون ثبت‌نام ببین. با گذرنامه
                        مهارتی نشان بده چه بلدی و ببین هر آگهی چقدر با تو جور است.
                    </p>
                    @if (Route::has('jobs.index'))
                        <div class="mt-8">
                            <x-button :href="route('jobs.index')" size="lg" class="max-sm:w-full">دیدن آگهی‌های شغلی</x-button>
                        </div>
                    @endif
                </div>

                <div class="grid gap-10 {{ $employers !== [] ? 'md:grid-cols-2' : '' }}">
                    @foreach (array_filter(['برای کارجو' => $seekers, 'برای کارفرما' => $employers]) as $audience => $items)
                        <div>
                            <h3 class="text-label font-semibold text-muted">{{ $audience }}</h3>
                            <ul class="mt-3 list-none border-t border-line-strong ps-0">
                                @foreach ($items as [$title, $text, $url])
                                    <li class="border-b border-line">
                                        <a href="{{ $url }}" class="group block py-4.5 no-underline hover:no-underline">
                                            <span class="block text-h4 text-ink group-hover:text-primary">{{ $title }}</span>
                                            <span class="mt-1 block text-note text-muted">{{ $text }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- مشاوره و خدمات: مشاور، بررسی گزارش و دایرکتوری آزمایشگاه‌ها. --}}
    @if ($services !== [])
        <section aria-labelledby="home-services" class="border-b border-line px-6 py-16 md:px-gutter md:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <h2 id="home-services" class="text-h1 text-ink">مشاوره و خدمات تخصصی بهداشت حرفه‌ای</h2>
                    <p class="mt-4 max-w-[40rem] text-copy text-body">
                        برای ارزیابی ریسک، اندازه‌گیری عوامل زیان‌آور یا بازبینی گزارش، متخصص تأییدشده پیدا کن.
                        پول تا تحویل کار نزد فرابهداشت امانت می‌ماند.
                    </p>
                    <ul class="mt-8 list-none border-t border-line-strong ps-0">
                        @foreach ($services as [$icon, $title, $text, $url])
                            <li class="border-b border-line">
                                <a href="{{ $url }}" class="group flex items-start gap-4 py-5 no-underline hover:no-underline">
                                    <span class="mt-1 shrink-0 text-muted group-hover:text-primary"><x-icon :name="$icon" :size="20" /></span>
                                    <span class="grow">
                                        <span class="block text-h4 text-ink group-hover:text-primary">{{ $title }}</span>
                                        <span class="mt-1 block text-note text-muted">{{ $text }}</span>
                                    </span>
                                    <span class="mt-1 text-muted transition-transform group-hover:-translate-x-1 group-hover:text-primary" aria-hidden="true">←</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <x-art name="home-consult" class="mx-auto hidden h-auto w-full max-w-md lg:block" />
            </div>
        </section>
    @endif

    {{-- یادگیری و کمک بیشتر: کاشی‌های ماژول‌ها کنار آمادگی آزمون، وبینار و بسته‌ها.
         با grow هر تعداد کاشی ردیف را پر می‌کند. --}}
    @if ($tiles !== [] || $grow !== [])
        <section aria-labelledby="home-help" class="border-b border-line px-6 py-16 md:px-gutter md:py-20">
            <h2 id="home-help" class="text-h2 text-ink">یادگیری و کمک بیشتر</h2>
            <p class="mt-2 max-w-[40rem] text-copy text-muted">دوره‌های آموزشی، آمادگی آزمون، وبینار و پرسش از متخصص؛ هرجا که یک قدم جلوتر لازم است.</p>

            <div class="mt-8 grid grid-cols-2 gap-x-6 gap-y-10 {{ $helpColumns }}">
                @foreach ($tiles as $section)
                    <div class="flex">
                        @include('core::home.tile', ['section' => $section])
                    </div>
                @endforeach
                @foreach ($grow as [$art, $title, $text, $cta, $url])
                    <section aria-labelledby="home-{{ $art }}"
                             class="flex flex-col gap-2 border-t border-line-strong pt-5">
                        <x-art :name="$art" class="mb-3 h-20 w-auto self-start" />
                        <h3 id="home-{{ $art }}" class="text-h4 text-ink">{{ $title }}</h3>
                        <p class="text-note text-muted">{{ $text }}</p>
                        <a href="{{ $url }}" class="mt-auto inline-flex min-h-touch items-center gap-1.5 self-start text-label font-semibold">
                            {{ $cta }} <span aria-hidden="true">←</span>
                        </a>
                    </section>
                @endforeach
            </div>
        </section>
    @endif

    {{-- دعوت به نویسندگی: هر کارشناس می‌تواند محتوا وارد سایت کند، پس از تأیید مدیر. --}}
    @if (Route::has('encyclopedia.writing-guide'))
        <section aria-labelledby="home-contribute" class="border-b border-line bg-surface-2 px-6 py-16 md:px-gutter md:py-20">
            <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <x-art name="home-writer" class="mb-6 h-32 w-auto" />
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
                            @foreach ($roles as [$art, $title, $text, $url])
                                <li class="border-b border-line">
                                    <a href="{{ $url }}" class="group flex items-center gap-4 py-4 no-underline hover:no-underline">
                                        <x-art :name="$art" class="h-16 w-auto shrink-0" />
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

    @includeIf('monetization::home.pro-band')

    {{-- پرسش‌های پرتکرار: همان متنی که در داده ساختاریافته FAQPage می‌آید. --}}
    @if ($faq !== [])
        <section aria-labelledby="home-faq" class="border-t border-line px-6 py-16 md:px-gutter md:py-20">
            <div class="grid items-start gap-12 lg:grid-cols-3 lg:gap-16">
                <div>
                    <h2 id="home-faq" class="text-h1 text-ink">پرسش‌های پرتکرار</h2>
                    <p class="mt-4 text-copy text-muted">کوتاه درباره این‌که فرابهداشت چیست و چه چیزی نیست.</p>
                    <x-art name="home-faq" class="mt-8 hidden h-40 w-auto lg:block" />
                </div>
                <div class="border-t border-line-strong lg:col-span-2">
                    @foreach ($faq as $item)
                        <details class="group border-b border-line">
                            <summary class="flex min-h-touch cursor-pointer list-none items-center justify-between gap-4 py-4.5 text-h4 text-ink
                                            hover:text-primary [&::-webkit-details-marker]:hidden">
                                {{ $item['question'] }}
                                <span class="shrink-0 text-muted transition-transform group-open:rotate-180" aria-hidden="true"><x-icon name="chevron-down" :size="18" /></span>
                            </summary>
                            <p class="max-w-[48rem] pb-5 text-copy text-body">{{ $item['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="px-6 py-10 md:px-gutter">
        <x-disclaimer>
            خروجی ابزارها و محتوای فرابهداشت جنبه آموزشی و کمک‌کارشناسی دارد و هیچ‌کدام ادعای تشخیص
            پزشکی، تأیید ایمنی قطعی یا انطباق قانونی قطعی ندارند. گواهی و نشان‌های داخلی سایت مدرک
            رسمی یا مجوز حرفه‌ای محسوب نمی‌شوند.
        </x-disclaimer>
    </section>
</x-layouts.public>
