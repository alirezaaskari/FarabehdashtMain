@props(['name', 'label', 'description' => null, 'checked' => false])

{{--
    کلید روشن/خاموش.
    یک checkbox واقعی است تا با کیبورد و صفحه‌خوان کار کند؛ ظاهرش با CSS ساخته می‌شود.
--}}

@php
    $id = $attributes->get('id', $name);
@endphp

<label for="{{ $id }}" {{ $attributes->only('class')->merge([
    'class' => 'flex min-h-touch cursor-pointer items-start justify-between gap-4',
]) }}>
    <span class="grow">
        <span class="block text-sm font-semibold text-ink">{{ $label }}</span>
        @if ($description)
            <span class="mt-1 block text-xs text-muted">{{ $description }}</span>
        @endif
    </span>

    <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="1"
           @checked($checked)
           class="peer sr-only">

    <span aria-hidden="true"
          class="relative mt-0.5 h-6 w-11 shrink-0 rounded-full bg-line-strong transition-colors
                 peer-checked:bg-primary peer-focus-visible:outline peer-focus-visible:outline-3
                 peer-focus-visible:outline-focus peer-focus-visible:outline-offset-2">
        <span class="absolute top-0.75 start-0.75 h-4.5 w-4.5 rounded-full bg-surface transition-transform
                     peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
    </span>
</label>
