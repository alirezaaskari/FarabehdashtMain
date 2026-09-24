@props(['summary', 'openFrom' => 'lg'])

{{--
    پنلی که روی موبایل جمع است و از یک عرض به بالا باز — برای فیلترها،
    فهرست مقاله و ستون کناری میزکار. روی گوشی این پنل‌ها صدها پیکسل بالای
    محتوای اصلی می‌نشستند.

    details بدون جاوااسکریپت هم کار می‌کند. اسکریپت درون‌خطی کوچک بلافاصله
    بعد از عنصر اجرا می‌شود، پیش از اولین نقاشی صفحه؛ پس روی دسکتاپ پنل
    بی‌پرش باز دیده می‌شود. عنوان روی دسکتاپ هم قابل کلیک می‌ماند تا صفحه
    بدون جاوااسکریپت هم هرگز محتوای دسترس‌ناپذیر نداشته باشد.
--}}

@php
    [$query, $chevron] = match ($openFrom) {
        'md' => ['(min-width: 48rem)', 'md:hidden'],
        default => ['(min-width: 64rem)', 'lg:hidden'],
    };
@endphp

<details {{ $attributes->class('group') }}>
    <summary class="flex min-h-touch cursor-pointer list-none items-center justify-between gap-3
                    [&::-webkit-details-marker]:hidden">
        {{ $summary }}
        <span class="{{ $chevron }} text-muted transition-transform group-open:rotate-180">
            <x-icon name="chevron-down" :size="20" />
        </span>
    </summary>

    {{ $slot }}
</details>
<script>(d => { if (matchMedia(@js($query)).matches) d.open = true; })(document.currentScript.previousElementSibling);</script>
