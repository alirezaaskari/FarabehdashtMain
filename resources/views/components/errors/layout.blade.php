@props(['code', 'title', 'message', 'shell' => false, 'art' => null])

{{--
    چیدمان مشترک صفحات خطا.

    قاعده: صفحه خطا همیشه یک راه خروج می‌دهد. بن‌بستِ «خطایی رخ داد» کاربر را
    از سایت بیرون می‌کند.

    shell: سربرگ و فوتر سایت، برای خطاهایی که سایت سالم است و فقط نشانی غلط
    است (۴۰۴، ۴۰۳). خطای ۵۰۰ و ۵۰۳ بدون پوسته می‌ماند: شاید همان پوسته خراب باشد.
--}}

<x-layouts.base :title="$title" theme="light" noindex :body-class="$shell ? 'flex flex-col' : ''">
    @if ($shell)
        <x-site.header />
    @endif

    <main id="main" @class([
        'mx-auto flex w-full max-w-2xl flex-col items-center justify-center gap-6 p-6 text-center',
        'min-h-screen' => ! $shell,
        'grow py-16' => $shell,
    ])>
        {{-- تصویر خطی فقط SVG درون‌خطی است و به پایگاه داده یا ماژولی تکیه ندارد،
             پس صفحه ۵۰۰ هم آن را بی‌خطر نشان می‌دهد. --}}
        @if ($art !== null)
            <x-art :name="$art" class="h-36 w-auto" />
        @endif

        {{-- عدد خطا تزئینی است و برای صفحه‌خوان خوانده نمی‌شود، ولی باید دیده
             شود: پس‌زمینه کم‌رنگ آن را محو می‌کرد. --}}
        <span class="rounded-xl bg-primary-soft px-5 py-2 text-display font-bold text-on-primary-soft"
              aria-hidden="true">@fa($code)</span>

        <h1 class="text-h1 font-bold text-ink">{{ $title }}</h1>

        <p class="max-w-lg text-lede text-muted">{{ $message }}</p>

        {{ $slot }}

        <div class="mt-2 flex flex-wrap items-center justify-center gap-3">
            <x-button href="/" variant="primary">بازگشت به صفحه اصلی</x-button>
        </div>
    </main>

    @if ($shell)
        <x-site.footer />
        <x-site.bottom-nav />
    @endif
</x-layouts.base>
