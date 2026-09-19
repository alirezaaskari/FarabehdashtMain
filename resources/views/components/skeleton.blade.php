@props(['lines' => 3, 'width' => '100%', 'height' => '0.875rem'])

{{--
    اسکلت بارگذاری.
    تصمیم سراسری: هیچ صفحه‌ای چرخنده وسط صفحه ندارد. اسکلت هم‌اندازه محتوای
    واقعی است تا چیدمان هنگام رسیدن داده نپرد.
--}}

@php
    $widths = ['70%', '95%', '85%', '60%', '90%'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-2.5']) }} aria-hidden="true">
    @for ($i = 0; $i < (int) $lines; $i++)
        <span class="skeleton-bar"
              style="width: {{ $lines === 1 ? $width : $widths[$i % count($widths)] }}; height: {{ $height }};"></span>
    @endfor
    <span class="sr-only">در حال بارگذاری…</span>
</div>
