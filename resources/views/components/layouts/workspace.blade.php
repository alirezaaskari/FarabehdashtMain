@props([
    'title' => null,
    'heading' => null,
    'lede' => null,
    'active' => null,
    'nav' => null,
    'sidebarTitle' => 'میزکار',
    'help' => null,
    'art' => null,
])

{{--
    میزکار و ابزارها: حالت تاریک اینجا فعال است و صفحه هرگز ایندکس نمی‌شود.

    پوسته همان پوسته صفحات عمومی است — سربرگ کامل و فوتر — به‌اضافه ستون
    کناری ۲۴۰ پیکسلی. کاربر با ورود به میزکار پیمایش سایت را از دست نمی‌دهد.

    active: کلید مورد فعال در پیمایش بالا. nav: کلید مورد فعال در ستون کناری.
    help: کلید راهنمای بخش در config/help.php، زیر عنوان صفحه. art: تصویر خطی کنار عنوان.
--}}

<x-layouts.base :title="$title" noindex bodyClass="flex min-h-screen flex-col">
    {{--
        نوار مشاهده به‌عنوان کاربر.
        وقتی مدیر میزکار را از چشم کاربر می‌بیند، باید همیشه بداند که در این
        حالت است و راه خروج جلوی چشمش باشد. رنگ هشدار عمدی است.
    --}}
    @if (Route::has('admin.impersonate.stop') && session()->has('admin.impersonator_id'))
        <div data-print="hide"
             class="flex flex-wrap items-center justify-center gap-3 bg-caution-soft px-4 py-2.5 text-label text-caution-ink">
            <span class="font-semibold">شما سایت را از چشم این کاربر می‌بینید. عملیات مالی در این حالت انجام نمی‌شود.</span>

            <form method="POST" action="{{ route('admin.impersonate.stop') }}">
                @csrf
                <x-button type="submit" variant="secondary" size="sm">بازگشت به حساب مدیریتی</x-button>
            </form>
        </div>
    @endif

    <x-site.header :active="$active" theme-toggle />

    <div class="flex grow flex-col gap-7 px-6 pt-8 pb-16 md:flex-row md:items-start md:gap-12 md:px-gutter">
        <x-site.sidebar :active="$nav" :title="$sidebarTitle" />

        <main id="main" class="min-w-0 grow">
            @isset($breadcrumb)
                <div class="mb-4">{{ $breadcrumb }}</div>
            @endisset

            @if ($heading)
                <x-page-header :title="$heading" :lede="$lede" :art="$art">
                    @isset($actions)
                        <x-slot:actions>{{ $actions }}</x-slot:actions>
                    @endisset
                </x-page-header>
            @endif

            @if ($help)
                <x-page-help :topic="$help" @class(['mt-5' => $heading]) />
            @endif

            <div @class(['mt-8' => $heading || $help])>{{ $slot }}</div>
        </main>
    </div>

    <x-site.footer />
    <x-site.bottom-nav active="workspace" />
</x-layouts.base>
