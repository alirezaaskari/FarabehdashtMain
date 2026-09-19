@props(['title' => null, 'heading' => null, 'sidebarTitle' => 'میزکار'])

{{--
    میزکار و ابزارها: حالت تاریک اینجا فعال است.
    این صفحات هرگز ایندکس نمی‌شوند.
--}}

<x-layouts.base :title="$title" noindex>
    <header data-print="hide"
            class="flex h-19 items-center gap-6 border-b border-line bg-surface px-6 md:px-10">
        <a href="/" class="flex h-touch shrink-0 items-center gap-2.5 no-underline hover:no-underline">
            <span class="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-on-primary">
                <x-icon name="shield" :size="18" />
            </span>
            <span class="text-xl font-extrabold tracking-tight text-ink">{{ config('app.name') }}</span>
        </a>

        <div class="grow"></div>

        <x-theme-toggle />
        <x-button href="#" variant="secondary" size="sm" icon="user">حساب من</x-button>
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
