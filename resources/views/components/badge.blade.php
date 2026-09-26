@props(['tone' => 'neutral', 'icon' => null])

@php
    $tones = [
        'neutral' => 'bg-surface-2 text-muted border-line',
        'primary' => 'bg-primary-soft text-on-primary-soft border-primary-line',
        'caution' => 'bg-caution-soft text-caution border-caution-line',
        'danger' => 'bg-danger-soft text-danger border-danger-line',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 h-6 px-2 rounded-sm border text-note font-semibold whitespace-nowrap '
        .($tones[$tone] ?? $tones['neutral']),
]) }}>
    @if ($icon)
        <x-icon :name="$icon" :size="13" :stroke="2.5" />
    @endif
    {{ $slot }}
</span>
