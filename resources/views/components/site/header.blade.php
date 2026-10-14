@props(['active' => null, 'themeToggle' => false])

{{--
    سربرگ مشترک همه صفحات — عمومی و میزکار.
    پروتوتایپ یک سربرگ دارد، نه دو تا: کاربر با ورود به میزکار پیمایش اصلی
    سایت را از دست نمی‌دهد. ارتفاع ۶۴ پیکسل و گاتر افقی با محتوا و فوتر یکی است.

    بخشی که نیست یا هنوز چیزی منتشرشده ندارد در پیمایش نمی‌آید: پیوند «#» یا
    صفحه «هنوز منتشر نشده» در بالاترین جای سایت فقط اعتماد را کم می‌کند.

    زیر ۱۰۲۴ پیکسل پیمایش به منوی کشویی می‌رود و زیر ۷۶۸ پیکسل نوار پایین
    (x-site.bottom-nav) هم هست. منو با details کار می‌کند تا بدون جاوااسکریپت
    هم باز شود؛ menu.js فقط بستن با Escape و کلیک بیرون را اضافه می‌کند.

    هر مقصد با Route::has محافظت می‌شود: پوسته سایت نباید به حضور یک ماژول
    گره بخورد، وگرنه حذف آن ماژول همه صفحه‌ها را می‌شکند (قاعده ۲).
--}}

@inject('shelves', 'App\Support\Site\Shelves')

@php
    // فهرستی که هنوز چیزی منتشرشده ندارد پیوند منو نمی‌گیرد ($shelves).
    $nav = array_filter([
        'encyclopedia' => Route::has('encyclopedia.index') ? ['دانشنامه', route('encyclopedia.index')] : null,
        'tools' => Route::has('tools.index') ? ['ابزارها', route('tools.index')] : null,
        'chemicals' => Route::has('chemicals.index') ? ['مواد شیمیایی', route('chemicals.index')] : null,
        'market' => $shelves->open('commerce.index') ? ['فروشگاه', route('commerce.index')] : null,
        'courses' => $shelves->open('courses.index') ? ['دوره‌ها', route('courses.index')] : null,
        'expert' => Route::has('expert.index') ? ['پرسش از متخصص', route('expert.index')] : null,
        'jobs' => $shelves->open('jobs.index') ? ['کاریابی', route('jobs.index')] : null,
    ]);

    // اشتراک تنها ردیفی است که با خاموش‌شدن یک کلید درآمدزایی هم پنهان
    // می‌شود، نه فقط با حذف ماژول. متغیر را ماژول درآمدزایی با یک View
    // Composer می‌گذارد؛ نبودنش یعنی «اشتراکی در کار نیست».
    if (($proSubscriptionOffered ?? false) && Route::has('monetization.plans')) {
        $nav['pro'] = ['اشتراک', route('monetization.plans')];
    }
@endphp

<header data-print="hide"
        class="relative z-30 flex h-16 shrink-0 items-center gap-4 border-b border-line bg-surface px-6 md:gap-8 md:px-gutter">
    <a href="{{ Route::has('home') ? route('home') : '/' }}"
       class="flex h-touch min-w-0 shrink-0 items-center gap-2.5 no-underline hover:no-underline">
        <span class="flex size-7 shrink-0 items-center justify-center rounded-sm bg-primary text-on-primary">
            <x-icon name="shield" :size="16" />
        </span>
        <span class="truncate text-h4 font-semibold text-ink">{{ config('app.name') }}</span>
    </a>

    <nav aria-label="پیمایش اصلی" class="hidden grow items-center gap-4 lg:flex xl:gap-5">
        @foreach ($nav as $key => [$label, $url])
            <a href="{{ $url }}"
               @class([
                   'inline-flex h-touch items-center whitespace-nowrap text-label no-underline hover:no-underline',
                   'font-semibold text-primary' => $active === $key,
                   'font-medium text-body hover:text-ink' => $active !== $key,
               ])
               @if ($active === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="flex grow items-center justify-end gap-2 lg:grow-0">
        @if (Route::has('workspace.search'))
            {{-- جست‌وجو هسته محصول است (CAS، ماده، ابزار)؛ در هیچ عرضی پنهان نمی‌شود. --}}
            {{-- از ۱۲۸۰ پیکسل کادر (کامل از ۱۵۳۶، مثل پروتوتایپ)؛ کمتر از آن آیکن، تا منو جا شود. --}}
            <form method="GET" action="{{ route('workspace.search') }}" role="search" class="relative hidden xl:block"
                  @if (Route::has('workspace.search.suggest')) data-search-suggest="{{ route('workspace.search.suggest') }}" @endif>
                <label for="site-search" class="sr-only">جست‌وجو در سایت</label>
                <input id="site-search" type="search" name="q" value="{{ request()->routeIs('workspace.search') ? request('q') : '' }}"
                       placeholder="جست‌وجوی ماده، ابزار، مقاله…"
                       class="h-touch w-52 rounded-md border border-line bg-surface-2 ps-10 pe-3 text-label text-ink 2xl:w-72
                              placeholder:text-muted">
                <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-muted">
                    <x-icon name="search" :size="18" />
                </span>
                <kbd aria-hidden="true" dir="ltr" data-numeric
                     class="pointer-events-none absolute inset-y-0 end-1 hidden items-center px-2 text-note text-muted 2xl:flex">Ctrl K</kbd>
                <div data-search-panel hidden
                     class="absolute end-0 top-full z-50 mt-1.5 max-h-[70vh] w-[26rem] overflow-y-auto rounded-lg border
                            border-line-strong bg-surface py-1"></div>
            </form>

            {{-- بی جاوااسکریپت صفحه جست‌وجو را باز می‌کند؛ با آن، پنل فرمان را. --}}
            <a href="{{ route('workspace.search') }}" aria-label="جست‌وجو" data-palette-open
               class="inline-flex h-touch w-11 shrink-0 items-center justify-center rounded-md text-ink hover:bg-surface-2 xl:hidden">
                <x-icon name="search" :size="20" />
            </a>

            @if (Route::has('workspace.search.suggest'))
                {{--
                    پنل فرمان (Ctrl+K): جست‌وجوی سراسری به‌اضافه کارها («گزارش تازه»،
                    «پرسش از متخصص»). کارها را هر ماژول با QuickActionSource می‌دهد
                    و فقط هنگام باز شدن از سرور گرفته می‌شود، نه با هر صفحه.
                --}}
                <dialog data-palette aria-label="جست‌وجو و کارها"
                        class="mx-auto mt-12 w-[26rem] rounded-lg border border-line-strong bg-surface p-0 text-ink">
                    <form method="GET" action="{{ route('workspace.search') }}" role="search"
                          data-search-suggest="{{ route('workspace.search.suggest') }}" data-search-palette>
                        <label for="palette-search" class="sr-only">جست‌وجو یا اجرای یک کار</label>
                        <div class="relative border-b border-line">
                            <input id="palette-search" type="search" name="q"
                                   placeholder="جست‌وجو یا کار، مثلاً «گزارش تازه»…"
                                   class="h-field w-full bg-surface ps-10 pe-3 text-control text-ink placeholder:text-muted">
                            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-muted">
                                <x-icon name="search" :size="18" />
                            </span>
                        </div>
                        <div data-search-panel class="max-h-[70vh] overflow-y-auto py-1"></div>
                        <p class="hidden border-t border-line-soft px-4 py-3 text-note text-muted md:block">
                            با کلیدهای جهت بین نتیجه‌ها بروید؛ Enter همه نتایج را باز می‌کند و Esc می‌بندد.
                        </p>
                    </form>
                </dialog>
            @endif
        @endif

        {{-- زیر ۶۴۰ پیکسل، زنگ اعلان‌ها جای کلید حالت تاریک را می‌گیرد. --}}
        @if ($themeToggle)
            <div class="hidden sm:flex"><x-theme-toggle /></div>
        @endif

        @auth
            {{-- شمار خوانده‌نشده را ماژول میزکار با View Composer می‌گذارد. --}}
            @if (Route::has('workspace.notifications'))
                @php $unread = (int) ($unreadNotifications ?? 0); @endphp
                <a href="{{ route('workspace.notifications') }}"
                   aria-label="{{ $unread > 0 ? 'اعلان‌ها، '.\App\Support\PersianDigits::from($unread).' خوانده‌نشده' : 'اعلان‌ها' }}"
                   class="relative inline-flex h-touch w-11 shrink-0 items-center justify-center rounded-md text-ink hover:bg-surface-2">
                    <x-icon name="bell" :size="20" />
                    @if ($unread > 0)
                        <span aria-hidden="true"
                              class="absolute end-1 top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-danger px-1 text-note font-semibold text-on-primary">@fa(min($unread, 99))</span>
                    @endif
                </a>
            @endif

            {{-- زیر ۶۴۰ پیکسل «میزکار» در نوار پایین است. --}}
            <div class="hidden sm:flex">
                @if (Route::has('workspace.dashboard'))
                    <x-button :href="route('workspace.dashboard')" variant="primary" size="sm"
                              class="whitespace-nowrap">میزکار</x-button>
                @elseif (Route::has('identity.profiles'))
                    <x-button :href="route('identity.profiles')" variant="primary" size="sm"
                              class="whitespace-nowrap">حساب من</x-button>
                @endif
            </div>

            {{-- زیر ۱۰۲۴ پیکسل «خروج» داخل منوی کشویی است. --}}
            @if (Route::has('identity.signout'))
                <form method="POST" action="{{ route('identity.signout') }}" data-signout class="hidden lg:block">
                    @csrf
                    <x-button type="submit" variant="ghost" size="sm" class="whitespace-nowrap">خروج</x-button>
                </form>
            @endif
        @else
            <x-button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm"
                      class="whitespace-nowrap">ورود / ثبت‌نام</x-button>
        @endauth

        <details data-menu class="lg:hidden">
            <summary aria-label="منوی سایت"
                     class="inline-flex h-touch w-11 cursor-pointer list-none items-center justify-center rounded-md
                            text-ink hover:bg-surface-2 [&::-webkit-details-marker]:hidden">
                <x-icon name="menu" :size="22" />
            </summary>

            <div class="absolute inset-x-0 top-full border-b border-line bg-surface px-6 pt-2 pb-5 md:px-gutter">
                <nav aria-label="پیمایش اصلی" class="flex flex-col">
                    @foreach ($nav as $key => [$label, $url])
                        <a href="{{ $url }}"
                           @class([
                               'flex min-h-touch items-center border-b border-line-soft text-copy no-underline hover:no-underline',
                               'font-semibold text-primary' => $active === $key,
                               'font-semibold text-ink hover:text-primary' => $active !== $key,
                           ])
                           @if ($active === $key) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>

                @auth
                    <div class="mt-4 flex flex-wrap gap-3">
                        @if (Route::has('workspace.dashboard'))
                            <x-button :href="route('workspace.dashboard')" variant="primary" size="sm">میزکار</x-button>
                        @elseif (Route::has('identity.profiles'))
                            <x-button :href="route('identity.profiles')" variant="primary" size="sm">حساب من</x-button>
                        @endif

                        @if (Route::has('identity.signout'))
                            <form method="POST" action="{{ route('identity.signout') }}" data-signout>
                                @csrf
                                <x-button type="submit" variant="secondary" size="sm">خروج</x-button>
                            </form>
                        @endif
                    </div>
                @endauth
            </div>
        </details>
    </div>
</header>
