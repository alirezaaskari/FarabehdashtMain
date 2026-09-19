@props(['title' => null, 'subtitle' => null, 'tone' => 'surface', 'level' => 2])

{{--
    کارت.
    level سطح هدینگ عنوان است؛ اگر کارت تنها عنوان صفحه باشد (مثل صفحه کد
    تأیید) با level=1 فراخوانی می‌شود تا صفحه دقیقاً یک H1 داشته باشد.
--}}

@php
    $tones = [
        'surface' => 'bg-surface border-line',
        'muted' => 'bg-surface-2 border-line',
        'primary' => 'bg-primary border-transparent text-on-primary',
    ];
@endphp

<section {{ $attributes->merge(['class' => 'rounded-xl border p-7 '.($tones[$tone] ?? $tones['surface'])]) }}>
    @if ($title)
        <h{{ $level }} @class([
            'text-xl font-extrabold',
            'text-ink' => $tone !== 'primary',
        ])>{{ $title }}</h{{ $level }}>
    @endif

    @if ($subtitle)
        <p @class([
            'mt-2 text-sm',
            'text-muted' => $tone !== 'primary',
        ])>{{ $subtitle }}</p>
    @endif

    <div @class(['mt-4' => $title || $subtitle])>
        {{ $slot }}
    </div>
</section>
