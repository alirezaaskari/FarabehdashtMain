{{--
    ستون «کمک بیشتر» صفحه اصلی (`HomeLayout::Tile`): پرسش از متخصص، دوره‌ها،
    فروشگاه. تا سه ستون کنار هم، هرکدام با خط نازک بالایش؛ تازه‌ترین ردیف
    بخش، اگر باشد، زیر توضیحش می‌آید.
--}}

@php
    $latest = $section->items[0] ?? null;
@endphp

<section aria-labelledby="home-{{ $section->key }}" class="flex flex-col gap-2 border-t border-line-strong pt-5">
    <h3 id="home-{{ $section->key }}" class="flex items-center gap-2.5 text-h4 text-ink">
        @if ($section->icon)
            <span class="text-muted"><x-icon :name="$section->icon" :size="18" /></span>
        @endif
        {{ $section->title }}
    </h3>
    <p class="text-copy text-muted">{{ $section->lede }}</p>

    @if ($latest !== null)
        <p class="text-note text-muted">
            تازه‌ترین:
            <a href="{{ $latest->url }}" class="font-semibold">{{ $latest->title }}</a>
        </p>
    @endif

    @if ($section->moreUrl && $section->moreLabel)
        <a href="{{ $section->moreUrl }}" class="mt-auto inline-flex min-h-touch items-center gap-1.5 self-start text-label font-semibold">
            {{ $section->moreLabel }} <span aria-hidden="true">←</span>
        </a>
    @endif
</section>
