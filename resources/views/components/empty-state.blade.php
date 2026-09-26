@props(['icon' => 'empty-box', 'title', 'description' => null, 'art' => null])

{{--
    حالت خالی.
    قاعده: هیچ حالت خالی فقط «داده‌ای وجود ندارد» نمی‌گوید.
    سه بخش دارد — چه چیزی نیست · چرا مهم است · قدم بعدی (اسلات action).
--}}

<div {{ $attributes->merge(['class' => 'flex flex-col items-start gap-2.5 rounded-xl border border-line bg-surface px-6 py-7']) }}>
    {{-- art (نام تصویر خطی) جای آیکون را می‌گیرد؛ برای حالت‌های خالی مهم صفحه. --}}
    @if ($art)
        <x-art :name="$art" class="h-28 w-auto" />
    @else
        <span class="text-dim">
            <x-icon :name="$icon" :size="26" :stroke="1.7" />
        </span>
    @endif

    <p class="text-copy font-semibold text-ink">{{ $title }}</p>

    @if ($description)
        <p class="text-note text-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
