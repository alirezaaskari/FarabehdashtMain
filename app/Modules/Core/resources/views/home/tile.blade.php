{{--
    کاشی دعوت صفحه اصلی (`HomeLayout::Tile`): پرسش از متخصص، دوره‌ها، فروشگاه.
    تا سه کاشی کنار هم؛ تازه‌ترین ردیف بخش، اگر باشد، زیر توضیحش می‌آید.
--}}

@php
    $latest = $section->items[0] ?? null;
@endphp

<section aria-labelledby="home-{{ $section->key }}" class="flex flex-col gap-3 rounded-xl border border-line bg-surface-2 p-6">
    @if ($section->icon)
        <span class="flex size-11 items-center justify-center rounded-md bg-primary-soft text-primary">
            <x-icon :name="$section->icon" :size="22" />
        </span>
    @endif

    <h3 id="home-{{ $section->key }}" class="text-h3 text-ink">{{ $section->title }}</h3>
    <p class="text-copy text-muted">{{ $section->lede }}</p>

    @if ($latest !== null)
        <p class="text-note text-muted">
            تازه‌ترین:
            <a href="{{ $latest->url }}" class="font-bold">{{ $latest->title }}</a>
        </p>
    @endif

    @if ($section->moreUrl && $section->moreLabel)
        <a href="{{ $section->moreUrl }}" class="mt-auto inline-flex min-h-touch items-center self-start text-label font-bold">
            {{ $section->moreLabel }} ←
        </a>
    @endif
</section>
