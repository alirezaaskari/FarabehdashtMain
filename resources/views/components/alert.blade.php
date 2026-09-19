@props(['tone' => 'info', 'title' => null])

{{--
    پیام وضعیت: success · error · caution · info
    قاعده لحن: نام موجودیت + دلیل محتمل + قدم بعدی. بدون علامت تعجب، بدون سرزنش.
--}}

@php
    $tones = [
        'success' => ['box' => 'bg-primary-soft border-primary-line', 'ink' => 'text-on-primary-soft', 'icon' => 'check'],
        'error' => ['box' => 'bg-danger-soft border-danger-line', 'ink' => 'text-danger', 'icon' => 'alert'],
        'caution' => ['box' => 'bg-caution-soft border-caution-line', 'ink' => 'text-caution', 'icon' => 'alert'],
        'info' => ['box' => 'bg-surface-2 border-line', 'ink' => 'text-muted', 'icon' => 'info'],
    ];

    $t = $tones[$tone] ?? $tones['info'];
@endphp

<div role="{{ in_array($tone, ['error', 'caution'], true) ? 'alert' : 'status' }}"
     {{ $attributes->merge(['class' => 'flex gap-3 rounded-lg border p-4 '.$t['box']]) }}>
    <span class="{{ $t['ink'] }} mt-0.5">
        <x-icon :name="$t['icon']" :size="19" :stroke="2.2" />
    </span>

    <div class="grow">
        @if ($title)
            <p class="text-sm font-bold text-ink">{{ $title }}</p>
        @endif
        <div @class(['text-sm text-body', 'mt-1.5' => $title])>{{ $slot }}</div>
    </div>
</div>
