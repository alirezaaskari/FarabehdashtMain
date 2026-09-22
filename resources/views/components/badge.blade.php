@props(['tone' => 'neutral', 'icon' => null])

@php
    $tones = [
        'neutral' => 'bg-disabled-surface text-muted',
        'primary' => 'bg-primary-soft text-on-primary-soft',
        'caution' => 'bg-caution-soft text-caution',
        'danger' => 'bg-danger-soft text-danger',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 h-7 px-3 rounded-full text-note font-bold '
        .($tones[$tone] ?? $tones['neutral']),
]) }}>
    @if ($icon)
        <x-icon :name="$icon" :size="13" :stroke="2.5" />
    @endif
    {{ $slot }}
</span>
