{{-- کارت یک ارائه‌دهنده در دایرکتوری؛ $photos کلید شناسه تصویر دارد. --}}
@php $photo = $profile->photo_id ? ($photos[$profile->photo_id] ?? null) : null; @endphp
<li>
    <a href="{{ $profile->publicUrl() }}"
       class="flex h-full min-h-touch items-start gap-4 rounded-xl border border-line bg-surface px-5 py-5 no-underline hover:bg-surface-2 hover:no-underline">
        @if ($photo)
            <img src="{{ $photo->url }}" alt="" width="56" height="56" loading="lazy" class="size-14 shrink-0 rounded-full object-cover">
        @else
            <span aria-hidden="true" class="flex size-14 shrink-0 items-center justify-center rounded-full bg-primary-soft text-h4 text-on-primary-soft">{{ mb_substr((string) $profile->display_name, 0, 1) }}</span>
        @endif
        <span class="flex min-w-0 flex-col gap-1">
            <span class="flex flex-wrap items-center gap-2">
                <span class="text-h4 text-ink">{{ $profile->display_name }}</span>
                <x-badge>{{ $profile->kind->label() }}</x-badge>
            </span>
            <span class="text-copy text-body">{{ $profile->headline }}</span>
            <span class="text-note text-muted">{{ $presenter->place($profile->province, $profile->city) }}</span>
            @if (($profile->offerings ?? []) !== [])
                <span class="text-note text-muted">{{ implode('، ', array_values(array_intersect_key($services, array_flip($profile->offerings)))) }}</span>
            @endif
        </span>
    </a>
</li>
