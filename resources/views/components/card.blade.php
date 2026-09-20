@props(['title' => null, 'subtitle' => null, 'tone' => 'surface', 'level' => 2, 'size' => 'md', 'heading' => null])

{{--
    کارت — همان قاب سفید پروتوتایپ: شعاع ۱۶، قاب یک‌پیکسلی و بالشتک ۲۸/۳۰.
    size='lg' برای کارت‌های اصلی (۳۲/۳۶) مثل پنل ورودی ابزار.

    level سطح هدینگ عنوان است؛ اگر کارت تنها عنوان صفحه باشد (مثل صفحه کد
    تأیید) با level=1 فراخوانی می‌شود تا صفحه دقیقاً یک H1 داشته باشد.
--}}

@php
    $tones = [
        'surface' => 'bg-surface border-line',
        'muted' => 'bg-surface-2 border-line',
        'primary' => 'bg-primary border-transparent text-on-primary',
        'caution' => 'bg-caution-soft border-caution-line text-caution-ink',
    ];

    $sizes = [
        'md' => 'px-6 py-7 md:px-7.5',
        'lg' => 'px-6 py-8 md:px-9',
    ];

    $headingSize = $heading ?? ($level === 1 ? 'text-h1' : 'text-h3');
@endphp

<section {{ $attributes->merge([
    'class' => 'rounded-xl border '.($sizes[$size] ?? $sizes['md']).' '.($tones[$tone] ?? $tones['surface']),
]) }}>
    @if ($title)
        <h{{ $level }} @class([$headingSize, 'text-ink' => $tone === 'surface' || $tone === 'muted'])>{{ $title }}</h{{ $level }}>
    @endif

    @if ($subtitle)
        <p @class([
            'mt-2.5 text-note',
            'text-muted' => $tone === 'surface' || $tone === 'muted',
        ])>{{ $subtitle }}</p>
    @endif

    <div @class(['mt-4' => $title || $subtitle])>
        {{ $slot }}
    </div>
</section>
