@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'error' => null,
    'suffix' => null,
    'required' => false,
    'numeric' => false,
])

{{--
    فیلد فرم.
    هر ورودی برچسب واقعی دارد — placeholder جای برچسب را نمی‌گیرد.
    خطا هم با رنگ، هم با آیکون و هم با متن منتقل می‌شود، نه فقط رنگ.

    numeric: مقدار عددی است؛ لاتین و چپ‌به‌راست می‌ماند. inputmode پیش‌فرض
    decimal است و فراخوان می‌تواند با inputmode="numeric" عوضش کند.
--}}

@php
    $id = $attributes->get('id', $name);
    $describedBy = collect([
        $hint ? $id.'-hint' : null,
        $error ? $id.'-error' : null,
    ])->filter()->implode(' ');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    <label for="{{ $id }}" class="block text-label font-bold text-ink mb-2">
        {{ $label }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
            <span class="sr-only">الزامی</span>
        @endif
    </label>

    <div class="flex items-center gap-2">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $value }}"
            @if ($required) required @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($numeric) data-numeric @endif
            {{ $attributes->except(['class', 'id'])->merge([
                'class' => 'grow h-field w-full rounded-md bg-surface px-3 text-control text-ink '
                    .'border '.($error ? 'border-danger border-2' : 'border-line-strong'),
                ...($numeric ? ['inputmode' => 'decimal'] : []),
            ]) }}
        >

        @if ($suffix)
            <span class="flex h-field w-16 shrink-0 items-center justify-center rounded-md
                         border border-line bg-surface-2 text-label font-bold text-muted"
                  dir="ltr" aria-hidden="true">{{ $suffix }}</span>
        @endif
    </div>

    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-2 text-note text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="mt-2 flex items-center gap-1.5 text-note font-semibold text-danger">
            <x-icon name="alert" :size="14" :stroke="2.4" />
            {{ $error }}
        </p>
    @endif
</div>
