@props(['label', 'value', 'unit' => null, 'note' => null, 'tone' => 'surface'])

@php
    $isPrimary = $tone === 'primary';
@endphp

<div {{ $attributes->merge([
    'class' => 'rounded-xl border p-6 '.($isPrimary ? 'bg-primary border-transparent' : 'bg-surface border-line'),
]) }}>
    <span class="block text-sm font-semibold {{ $isPrimary ? 'text-primary-soft' : 'text-muted' }}">
        {{ $label }}
    </span>

    <div class="mt-2 flex items-baseline gap-2">
        <span class="text-3xl font-extrabold {{ $isPrimary ? 'text-on-primary' : 'text-ink' }}" data-numeric>
            {{ $value }}
        </span>
        @if ($unit)
            <span class="text-sm font-semibold {{ $isPrimary ? 'text-primary-soft' : 'text-muted' }}">{{ $unit }}</span>
        @endif
    </div>

    @if ($note)
        <span class="mt-1.5 block text-xs {{ $isPrimary ? 'text-primary-soft' : 'text-muted' }}">{{ $note }}</span>
    @endif
</div>
