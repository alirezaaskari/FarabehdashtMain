@props(['title' => null, 'subtitle' => null, 'tone' => 'surface'])

@php
    $tones = [
        'surface' => 'bg-surface border-line',
        'muted' => 'bg-surface-2 border-line',
        'primary' => 'bg-primary border-transparent text-on-primary',
    ];
@endphp

<section {{ $attributes->merge(['class' => 'rounded-xl border p-7 '.($tones[$tone] ?? $tones['surface'])]) }}>
    @if ($title)
        <h2 @class([
            'text-xl font-extrabold',
            'text-ink' => $tone !== 'primary',
        ])>{{ $title }}</h2>
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
