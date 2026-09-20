@props(['items' => []])

{{--
    نوار مسیر صفحه.
    items: آرایه‌ای از [عنوان, نشانی|null]. آخرین مورد همیشه بدون پیوند و
    پررنگ است، چون صفحه جاری است.
--}}

<nav aria-label="مسیر صفحه" data-print="hide"
     {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2 text-note text-muted']) }}>
    @foreach ($items as [$label, $url])
        @if (! $loop->first)
            <span aria-hidden="true">/</span>
        @endif

        @if ($url && ! $loop->last)
            <a href="{{ $url }}" class="inline-flex h-touch items-center text-note">{{ $label }}</a>
        @else
            <span class="inline-flex h-touch items-center font-semibold text-ink"
                  aria-current="page">{{ $label }}</span>
        @endif
    @endforeach
</nav>
