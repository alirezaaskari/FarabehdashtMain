@props(['icon' => 'empty-box', 'title', 'description' => null])

{{--
    حالت خالی.
    قاعده: هیچ حالت خالی فقط «داده‌ای وجود ندارد» نمی‌گوید.
    سه بخش دارد — چه چیزی نیست · چرا مهم است · قدم بعدی (اسلات action).
--}}

<div {{ $attributes->merge(['class' => 'flex flex-col items-start gap-2.5 rounded-lg border border-line bg-surface p-6']) }}>
    <span class="text-dim">
        <x-icon :name="$icon" :size="26" :stroke="1.8" />
    </span>

    <p class="text-base font-bold text-ink">{{ $title }}</p>

    @if ($description)
        <p class="text-sm text-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
