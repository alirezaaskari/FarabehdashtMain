@props(['active' => null, 'themeToggle' => false])

{{--
    سربرگ مشترک همه صفحات — عمومی و میزکار.
    پروتوتایپ یک سربرگ دارد، نه دو تا: کاربر با ورود به میزکار پیمایش اصلی
    سایت را از دست نمی‌دهد. ارتفاع ۷۶ پیکسل و گاتر افقی با محتوا و فوتر یکی است.

    مقصدهای «#» بخش‌هایی هستند که هنوز ساخته نشده‌اند؛ با رسیدن هر بخش،
    فقط همین آرایه عوض می‌شود.

    هر مقصد با Route::has محافظت می‌شود: پوسته سایت نباید به حضور یک ماژول
    گره بخورد، وگرنه حذف آن ماژول همه صفحه‌ها را می‌شکند (قاعده ۲).
--}}

@php
    $nav = [
        'encyclopedia' => ['دانشنامه', Route::has('encyclopedia.index') ? route('encyclopedia.index') : '#'],
        'tools' => ['ابزارها', Route::has('tools.index') ? route('tools.index') : '#'],
        'chemicals' => ['مواد شیمیایی', Route::has('chemicals.index') ? route('chemicals.index') : '#'],
        'market' => ['فروشگاه', '#'],
        'courses' => ['دوره‌ها', '#'],
        'jobs' => ['کاریابی', '#'],
        'consulting' => ['مشاوره', '#'],
    ];
@endphp

<header data-print="hide"
        class="flex h-19 shrink-0 items-center gap-4 border-b border-line bg-surface px-6 md:gap-8 md:px-gutter">
    <a href="{{ Route::has('home') ? route('home') : '/' }}"
       class="flex h-touch min-w-0 shrink-0 items-center gap-2.5 no-underline hover:no-underline">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary text-on-primary">
            <x-icon name="shield" :size="18" />
        </span>
        <span class="truncate text-xl font-extrabold tracking-tight text-ink">{{ config('app.name') }}</span>
    </a>

    <nav aria-label="پیمایش اصلی" class="hidden grow items-center gap-5 lg:flex">
        @foreach ($nav as $key => [$label, $url])
            <a href="{{ $url }}"
               @class([
                   'inline-flex h-touch items-center text-sm no-underline hover:no-underline',
                   'font-bold text-primary' => $active === $key,
                   'font-semibold text-ink hover:text-primary' => $active !== $key,
               ])
               @if ($active === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="flex grow items-center justify-end gap-2 lg:grow-0">
        @if ($themeToggle)
            <x-theme-toggle />
        @endif

        @auth
            @if (Route::has('identity.profiles'))
                <x-button :href="route('identity.profiles')" variant="primary" size="sm"
                          class="whitespace-nowrap">حساب من</x-button>
            @endif

            @if (Route::has('identity.signout'))
                <form method="POST" action="{{ route('identity.signout') }}">
                    @csrf
                    <x-button type="submit" variant="ghost" size="sm" class="whitespace-nowrap">خروج</x-button>
                </form>
            @endif
        @else
            <x-button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm"
                      class="whitespace-nowrap">ورود / ثبت‌نام</x-button>
        @endauth
    </div>
</header>
