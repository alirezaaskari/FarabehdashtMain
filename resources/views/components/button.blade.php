@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'block' => false,
])

{{--
    دکمه استاندارد.
    قاعده دسترس‌پذیری: هدف لمسی هرگز کمتر از ۴۴ پیکسل نیست و دکمه فقط-آیکون
    باید aria-label بگیرد — کامپوننت آن را اجبار نمی‌کند، بررسی خودکار CI می‌کند.
--}}

@php
    $variants = [
        'primary' => 'bg-primary text-on-primary border border-transparent hover:bg-primary-deep',
        'secondary' => 'bg-surface text-ink border border-line-strong hover:bg-surface-2',
        'ghost' => 'bg-transparent text-primary border border-transparent hover:bg-primary-soft',
        'danger' => 'bg-surface text-danger border border-danger-line hover:bg-danger-soft',
        'on-dark' => 'bg-surface text-primary-deep border border-transparent',
    ];

    $sizes = [
        'sm' => 'h-touch px-4 text-label',
        'md' => 'h-field px-5 text-label',
        'lg' => 'h-field-lg px-6 text-control',
    ];

    $base = 'inline-flex items-center justify-center gap-2 rounded-md font-bold '
        .'transition-colors no-underline hover:no-underline cursor-pointer '
        .'disabled:cursor-not-allowed disabled:bg-disabled-surface disabled:text-disabled-ink '
        .'disabled:border-transparent disabled:hover:bg-disabled-surface';

    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md']).' '.($block ? 'w-full' : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-icon :name="$icon" :size="18" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-icon :name="$icon" :size="18" />
        @endif
        {{ $slot }}
    </button>
@endif
