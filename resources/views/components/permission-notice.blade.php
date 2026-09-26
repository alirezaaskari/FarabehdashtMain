@props(['title', 'description' => null])

{{--
    حالت «دسترسی ندارید» — یکی از شش حالت اجباری هر کامپوننت.
    هرگز صفحه را بن‌بست نمی‌کند: همیشه یک مسیر روشن پیش پای کاربر می‌گذارد.
--}}

<div {{ $attributes->merge(['class' => 'flex flex-col items-start gap-2.5 rounded-lg border border-line bg-surface p-6']) }}>
    <span class="text-caution">
        <x-icon name="lock" :size="26" :stroke="1.8" />
    </span>

    <p class="text-h4 font-semibold text-ink">{{ $title }}</p>

    @if ($description)
        <p class="text-label text-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
