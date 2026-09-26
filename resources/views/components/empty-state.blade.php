@props(['icon' => 'empty-box', 'title', 'description' => null])

{{--
    حالت خالی.
    قاعده: هیچ حالت خالی فقط «داده‌ای وجود ندارد» نمی‌گوید.
    سه بخش دارد — چه چیزی نیست · چرا مهم است · قدم بعدی (اسلات action).
--}}

<div {{ $attributes->merge(['class' => 'flex flex-col items-start gap-2.5 rounded-xl border border-line bg-surface px-6 py-7']) }}>
    <span class="text-dim">
        <x-icon :name="$icon" :size="26" :stroke="1.7" />
    </span>

    <p class="text-copy font-semibold text-ink">{{ $title }}</p>

    @if ($description)
        <p class="text-note text-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
