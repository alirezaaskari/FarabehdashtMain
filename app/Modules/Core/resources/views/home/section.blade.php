{{--
    یک بخش صفحه اصلی. شکلش را `layout` خود بخش تعیین می‌کند، نه نام ماژول.

    بی‌قاب: عنوان، بند معرفی و محتوا روی زمینه صفحه، با خط نازک جداکننده.
    بخش نیمه‌عرض (فهرست و پنل) کنار بخش نیمه‌عرض دیگر می‌نشیند.
--}}

@use('App\Support\Home\HomeLayout')

<section aria-labelledby="home-{{ $section->key }}" class="flex flex-col">
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
        <div class="min-w-0">
            <h2 id="home-{{ $section->key }}" class="text-h2 text-ink">{{ $section->title }}</h2>
            <p class="mt-2 max-w-[44rem] text-copy text-muted">{{ $section->lede }}</p>
        </div>

        @if ($section->moreUrl && $section->moreLabel && $section->layout !== HomeLayout::Panel)
            <a href="{{ $section->moreUrl }}" class="inline-flex min-h-touch items-center gap-1.5 text-label font-semibold">
                {{ $section->moreLabel }} <span aria-hidden="true">←</span>
            </a>
        @endif
    </div>

    @switch($section->layout)
        @case(HomeLayout::Panel)
            @if ($section->searchUrl)
                <form method="GET" action="{{ $section->searchUrl }}" role="search" class="mt-6">
                    <label for="home-{{ $section->key }}-q" class="sr-only">جست‌وجو در {{ $section->title }}</label>
                    <input id="home-{{ $section->key }}-q" type="search" name="q"
                           placeholder="{{ $section->searchPlaceholder }}"
                           class="h-field-lg w-full rounded-md border border-line-strong bg-surface px-4 text-control text-ink
                                  placeholder:text-dim">
                </form>
            @endif

            <ul class="mt-4 flex list-none flex-wrap gap-2 ps-0">
                @foreach ($section->items as $item)
                    <li>
                        <a href="{{ $item->url }}"
                           class="inline-flex min-h-touch items-center rounded-md border border-line px-3.5 text-label font-medium
                                  text-body no-underline hover:border-line-strong hover:text-ink hover:no-underline">
                            {{ $item->title }}
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($section->moreUrl && $section->moreLabel)
                <div class="mt-6">
                    <x-button :href="$section->moreUrl" variant="secondary">{{ $section->moreLabel }}</x-button>
                </div>
            @endif
            @break

        @case(HomeLayout::List)
            <ul class="mt-6 list-none border-t border-line-strong ps-0">
                @foreach ($section->items as $item)
                    <li class="border-b border-line">
                        <a href="{{ $item->url }}" class="group block py-4.5 no-underline hover:no-underline">
                            <h3 class="text-h4 text-ink group-hover:text-primary">{{ $item->title }}</h3>
                            @if ($item->kicker !== '' || $item->meta)
                                <span class="mt-1 flex flex-wrap gap-x-2 text-note text-muted">
                                    @if ($item->kicker !== '')
                                        <span class="font-semibold text-body">{{ $item->kicker }}</span>
                                    @endif
                                    @if ($item->kicker !== '' && $item->meta)
                                        <span aria-hidden="true">·</span>
                                    @endif
                                    @if ($item->meta)
                                        <span>{{ $item->meta }}</span>
                                    @endif
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            @break

        @case(HomeLayout::Chips)
            <ul class="mt-6 flex list-none flex-wrap gap-2 ps-0">
                @foreach ($section->items as $item)
                    <li>
                        <a href="{{ $item->url }}"
                           class="flex min-h-touch flex-col justify-center rounded-md border border-line px-4 py-2
                                  no-underline hover:border-line-strong hover:no-underline">
                            <span class="text-label font-semibold text-ink">{{ $item->title }}</span>
                            @if ($item->meta)
                                <span class="text-note text-muted" data-numeric>{{ $item->meta }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            @break

        @default
            {{-- شبکه‌ای با خط نازک بین خانه‌ها؛ خانه برجسته (`feature`) آخرِ شبکه با رنگ اصلی. --}}
            <ul class="mt-8 grid list-none grid-cols-2 gap-px overflow-hidden rounded-xl border border-line bg-line ps-0 lg:grid-cols-4">
                @foreach ($section->items as $item)
                    <li class="bg-surface">
                        <a href="{{ $item->url }}" class="group flex h-full flex-col p-5 no-underline hover:bg-surface-2 hover:no-underline md:p-6">
                            <span class="flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2.5">
                                    @if ($item->icon)
                                        <span class="text-muted group-hover:text-primary"><x-icon :name="$item->icon" :size="18" /></span>
                                    @endif
                                    <h3 class="text-copy font-semibold text-ink md:text-h4">{{ $item->title }}</h3>
                                </span>
                                @if ($item->kicker !== '')
                                    <span class="shrink-0 text-note text-muted">{{ $item->kicker }}</span>
                                @endif
                            </span>

                            @if ($item->summary !== '')
                                <p class="mt-2 hidden text-note text-muted md:block">{{ Str::limit($item->summary, 120) }}</p>
                            @endif

                            @if ($item->meta)
                                <span class="mt-auto hidden pt-4 text-note text-muted md:block">{{ $item->meta }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach

                @if ($section->feature)
                    @php
                        // خانه برجسته جای خالی آخر شبکه را پر می‌کند تا خانه خاکستری بی‌محتوا نماند.
                        $count = count($section->items);
                        $span = [
                            'mobile' => $count % 2 === 1 ? 'col-span-1' : 'col-span-2',
                            'desktop' => ['lg:col-span-4', 'lg:col-span-3', 'lg:col-span-2', 'lg:col-span-1'][$count % 4],
                        ];
                    @endphp
                    <li class="{{ $span['mobile'] }} {{ $span['desktop'] }} bg-primary-soft">
                        <a href="{{ $section->feature->url }}"
                           class="group flex h-full flex-col gap-2 p-5 no-underline hover:no-underline md:p-6">
                            <span class="flex items-center gap-2.5 text-primary">
                                @if ($section->feature->icon)
                                    <x-icon :name="$section->feature->icon" :size="18" />
                                @endif
                                <h3 class="text-copy font-semibold text-ink md:text-h4">{{ $section->feature->title }}</h3>
                            </span>
                            <p class="text-note text-body">{{ $section->feature->summary }}</p>
                            <span class="mt-auto inline-flex items-center gap-1.5 pt-2 text-label font-semibold text-primary">
                                {{ $section->feature->kicker }}
                                <span aria-hidden="true" class="transition-transform group-hover:-translate-x-1">←</span>
                            </span>
                        </a>
                    </li>
                @endif
            </ul>
    @endswitch
</section>
