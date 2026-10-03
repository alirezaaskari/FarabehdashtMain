{{--
    تکه پنل پیشنهاد فوری؛ بدون پوسته، داخل سربرگ، صفحه جست‌وجو یا پنل فرمان
    می‌نشیند. پنل فرمان کارها را بالای نتایج دارد و بی عبارت هم چیزی نشان می‌دهد.
--}}
@php
    $actions ??= [];
    $palette ??= false;
    $searchable = $query->isSearchable();
@endphp

@if ($actions !== [])
    <section class="border-b border-line-soft py-2 last:border-b-0" aria-label="کارها">
        <h2 class="px-4 pt-1 text-note font-semibold text-muted">کارها</h2>
        <ul class="list-none ps-0">
            @foreach ($actions as $action)
                <li>
                    <a href="{{ $action->url }}" data-suggestion
                       class="flex min-h-touch items-center gap-3 px-4 text-label font-semibold text-ink
                              no-underline hover:bg-surface-2 hover:no-underline focus-visible:bg-surface-2">
                        <x-icon name="forward" :size="16" class="shrink-0 text-muted" />
                        <span class="min-w-0 truncate">{{ $action->title }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif

@if ($searchable)
    @if ($groups === [] && $actions === [])
        <p class="px-4 py-3 text-note text-muted" role="status">نتیجه‌ای برای «{{ $query->raw }}» پیدا نشد.</p>
    @elseif ($groups !== [])
        @foreach ($groups as $group)
            <section class="border-b border-line-soft py-2 last:border-b-0" aria-label="{{ $group->title }}">
                <h2 class="px-4 pt-1 text-note font-semibold text-muted">{{ $group->title }}</h2>
                <ul class="list-none ps-0">
                    @foreach ($group->hits as $hit)
                        <li>
                            <a href="{{ $hit->url }}" data-suggestion
                               class="flex min-h-touch items-center justify-between gap-3 px-4 text-label font-semibold text-ink
                                      no-underline hover:bg-surface-2 hover:no-underline focus-visible:bg-surface-2">
                                <span class="min-w-0 truncate">{{ $hit->title }}</span>
                                @if ($hit->code)
                                    <span dir="ltr" data-numeric class="shrink-0 text-note text-muted">{{ $hit->code }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <a href="{{ route('workspace.search', ['q' => $query->raw]) }}" data-suggestion
           class="flex min-h-touch items-center px-4 text-label font-semibold no-underline hover:bg-surface-2 hover:no-underline">
            همه نتایج برای «{{ $query->raw }}» ←
        </a>
    @endif
@elseif ($palette && $actions === [])
    <p class="px-4 py-3 text-note text-muted">نام ماده، ابزار، مقاله یا شماره CAS را بنویسید.</p>
@endif
