@props(['code', 'title', 'message'])

{{--
    چیدمان مشترک صفحات خطا.

    قاعده: صفحه خطا همیشه یک راه خروج می‌دهد. بن‌بستِ «خطایی رخ داد» کاربر را
    از سایت بیرون می‌کند.
--}}

<x-layouts.base :title="$title" theme="light" noindex>
    <main id="main" class="mx-auto flex min-h-screen max-w-2xl flex-col items-center justify-center gap-6 p-6 text-center">
        {{-- عدد خطا تزئینی است و برای صفحه‌خوان خوانده نمی‌شود، ولی باید دیده
             شود: پس‌زمینه کم‌رنگ آن را محو می‌کرد. --}}
        <span class="rounded-xl bg-primary-soft px-5 py-2 text-5xl font-extrabold text-on-primary-soft"
              aria-hidden="true">@fa($code)</span>

        <h1 class="text-3xl font-extrabold text-ink">{{ $title }}</h1>

        <p class="max-w-lg text-base text-muted">{{ $message }}</p>

        {{ $slot }}

        <div class="mt-2 flex flex-wrap items-center justify-center gap-3">
            <x-button href="/" variant="primary">بازگشت به صفحه اصلی</x-button>
        </div>
    </main>
</x-layouts.base>
