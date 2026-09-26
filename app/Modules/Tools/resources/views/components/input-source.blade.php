@props(['key', 'text'])

{{--
    «این مقدار را از کجا بیاورم؟» زیر هر ورودی ابزار: با چه دستگاه یا روشی
    به دست می‌آید و از کجا (برگه اطلاعات ایمنی، جدول حد مجاز، کاتالوگ سازنده…).
    متن از config/guides.php ماژول ابزارها. جمع است تا فرم کوتاه بماند.
--}}

<details data-input-source="{{ $key }}" {{ $attributes->class('group') }}>
    <summary class="inline-flex min-h-touch cursor-pointer list-none items-center gap-1.5 text-note font-bold text-primary
                    [&::-webkit-details-marker]:hidden">
        <x-icon name="info" :size="16" />
        این مقدار را از کجا بیاورم؟
        <span class="transition-transform group-open:rotate-180"><x-icon name="chevron-down" :size="16" /></span>
    </summary>

    <p class="rounded-md border-s-4 border-primary-line bg-surface-2 p-3 text-note text-body">
        {{ \App\Support\Help\HelpText::render($text) }}
    </p>
</details>
