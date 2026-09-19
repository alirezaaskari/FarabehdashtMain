@props(['title' => null])

{{-- چیدمان چاپ: بدون منو، بدون دکمه، فقط محتوا و منابع. --}}

<x-layouts.base :title="$title" theme="light" noindex body-class="bg-surface">
    <main id="main" class="mx-auto max-w-[794px] p-12">
        {{ $slot }}
    </main>
</x-layouts.base>
