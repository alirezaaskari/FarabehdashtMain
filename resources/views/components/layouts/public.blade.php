@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'noindex' => false,
    'active' => null,
    'seo' => null,
    'padded' => true,
])

{{--
    صفحات عمومی: همیشه روشن، چون هدفشان ایندکس‌شدن و خوانایی است.

    پهنای محتوا محدود نمی‌شود؛ پروتوتایپ یک ستون تمام‌عرض با گاتر ثابت دارد،
    نه یک ستون باریک وسط صفحه. اسلات breadcrumb درست زیر سربرگ می‌نشیند.

    padded=false برای صفحاتی که خودشان نوار تمام‌عرض دارند (صفحه اصلی):
    آن‌وقت گاتر را هر بخش خودش می‌گذارد.
--}}

<x-layouts.base :title="$title" :description="$description" :canonical="$canonical"
                :noindex="$noindex" :seo="$seo" theme="light"
                bodyClass="flex min-h-screen flex-col">
    <x-site.header :active="$active" />

    {{-- پیام یک‌باره‌ای که مسیر دیگری پیش از هدایت به این صفحه گذاشته (مثلاً فروش متوقف). --}}
    @if (session('notice'))
        <div class="shrink-0 px-6 pt-4 md:px-gutter">
            <x-alert tone="caution">{{ session('notice') }}</x-alert>
        </div>
    @endif

    @isset($breadcrumb)
        <div class="shrink-0 px-6 pt-4 md:px-gutter">{{ $breadcrumb }}</div>
    @endisset

    <main id="main" @class([
        'grow',
        'px-6 pb-12 md:px-gutter' => $padded,
        'pt-8' => $padded && ! isset($breadcrumb),
        'pt-6' => $padded && isset($breadcrumb),
    ])>
        {{ $slot }}
    </main>

    <x-site.footer />
    <x-site.bottom-nav :active="$active" />
</x-layouts.base>
