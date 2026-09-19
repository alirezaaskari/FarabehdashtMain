@props(['title' => null, 'heading' => null, 'sidebarTitle' => 'میزکار'])

{{--
    میزکار و ابزارها: حالت تاریک اینجا فعال است.
    این صفحات هرگز ایندکس نمی‌شوند.
--}}

<x-layouts.base :title="$title" noindex>
    <header data-print="hide"
            class="flex h-19 items-center gap-3 border-b border-line bg-surface px-4 md:gap-6 md:px-10">
        <a href="/" class="flex h-touch min-w-0 shrink items-center gap-2.5 no-underline hover:no-underline">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary text-on-primary">
                <x-icon name="shield" :size="18" />
            </span>
            <span class="truncate text-xl font-extrabold tracking-tight text-ink">{{ config('app.name') }}</span>
        </a>

        <div class="grow"></div>

        <div class="flex shrink-0 items-center gap-2">
            <x-theme-toggle />

            {{--
                چیدمان به ماژول هویت گره نمی‌خورد: اگر آن ماژول غیرفعال باشد،
                مسیرهایش وجود ندارند و این دکمه‌ها هم ساده حذف می‌شوند.
                روی موبایل «حساب من» فقط آیکون است تا سربرگ سرریز نکند.
            --}}
            @if (Route::has('identity.profiles'))
                <x-button :href="route('identity.profiles')" variant="secondary" size="sm"
                          icon="user" class="max-sm:px-3" aria-label="حساب من">
                    <span class="max-sm:sr-only whitespace-nowrap">حساب من</span>
                </x-button>
            @endif

            @if (Route::has('identity.signout'))
                <form method="POST" action="{{ route('identity.signout') }}">
                    @csrf
                    <x-button type="submit" variant="ghost" size="sm" class="whitespace-nowrap">خروج</x-button>
                </form>
            @endif
        </div>
    </header>

    <div class="flex flex-col gap-7 p-6 md:flex-row md:p-10">
        @isset($sidebar)
            <aside class="w-full shrink-0 rounded-xl border border-line bg-surface p-5 md:w-60"
                   data-print="hide">
                <h2 class="mb-3.5 text-xs font-bold text-muted">{{ $sidebarTitle }}</h2>
                <nav aria-label="{{ $sidebarTitle }}">{{ $sidebar }}</nav>
            </aside>
        @endisset

        <main id="main" class="grow">
            @if ($heading)
                <h1 class="text-3xl font-extrabold tracking-tight text-ink">{{ $heading }}</h1>
            @endif

            <div @class(['mt-6' => $heading])>{{ $slot }}</div>
        </main>
    </div>
</x-layouts.base>
