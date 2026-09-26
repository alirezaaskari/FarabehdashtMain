{{--
    یک بخش صفحه اصلی. شکلش را `layout` خود بخش تعیین می‌کند، نه نام ماژول.

    framed: بخش نیمه‌عرض داخل قاب خودش می‌نشیند (دانشنامه در کارت سفید، بانک
    مواد در پنل رنگی)، مثل ردیف دوستونی پروتوتایپ.
--}}

@use('App\Support\Home\HomeLayout')

@php
    $panel = $section->layout === HomeLayout::Panel;
@endphp

<section aria-labelledby="home-{{ $section->key }}"
         @class([
             'mt-6 first:mt-0 lg:mt-0 flex flex-col rounded-xl px-6 py-7 md:px-8 md:py-8' => $framed,
             'border border-line bg-surface' => $framed && ! $panel,
             'bg-primary text-on-primary' => $panel,
         ])>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 id="home-{{ $section->key }}" @class([$framed ? 'text-h2' : 'text-h1', 'text-ink' => ! $panel])>{{ $section->title }}</h2>
            <p @class(['mt-2.5 max-w-[46rem] text-copy', 'text-muted' => ! $panel, 'text-primary-soft' => $panel])>{{ $section->lede }}</p>
        </div>

        @if ($section->moreUrl && $section->moreLabel && ! $panel)
            <a href="{{ $section->moreUrl }}" class="inline-flex min-h-touch items-center text-copy font-bold">
                {{ $section->moreLabel }} ←
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
                           class="h-field-lg w-full rounded-lg border border-primary-line bg-primary-deep px-4 text-control
                                  text-on-primary placeholder:text-primary-soft">
                </form>
            @endif

            <ul class="mt-4 flex list-none flex-wrap gap-2.5 ps-0">
                @foreach ($section->items as $item)
                    <li>
                        <a href="{{ $item->url }}"
                           class="inline-flex min-h-touch items-center rounded-full bg-primary-deep px-4 text-label font-bold
                                  text-on-primary no-underline hover:bg-surface hover:text-primary hover:no-underline">
                            {{ $item->title }}
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($section->moreUrl && $section->moreLabel)
                <div class="mt-auto pt-8">
                    <x-button :href="$section->moreUrl" variant="on-dark">{{ $section->moreLabel }}</x-button>
                </div>
            @endif
            @break

        @case(HomeLayout::List)
            <ul class="mt-4 list-none ps-0">
                @foreach ($section->items as $item)
                    <li class="border-b border-line-soft last:border-b-0">
                        <a href="{{ $item->url }}" class="block py-4.5 no-underline hover:no-underline">
                            @if ($item->kicker !== '')
                                <span class="text-note font-bold text-caution">{{ $item->kicker }}</span>
                            @endif
                            <h3 class="mt-1.5 text-h4 text-ink hover:text-primary">{{ $item->title }}</h3>
                            @if ($item->meta)
                                <span class="mt-1.5 block text-note text-muted">{{ $item->meta }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            @break

        @case(HomeLayout::Chips)
            <ul class="mt-7 flex list-none flex-wrap gap-3 ps-0">
                @foreach ($section->items as $item)
                    <li>
                        <a href="{{ $item->url }}"
                           class="flex min-h-touch flex-col justify-center rounded-lg border border-line
                                  bg-surface px-4 py-2 no-underline hover:border-primary-line hover:no-underline">
                            <span class="text-copy font-bold text-ink">{{ $item->title }}</span>
                            @if ($item->meta)
                                <span class="text-note text-muted" data-numeric>{{ $item->meta }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            @break

        @default
            {{-- روی گوشی دوستونی و فشرده (فقط آیکن و عنوان)؛ کارت برجسته (`feature`) آخر ردیف، پررنگ. --}}
            <ul class="mt-7 grid list-none grid-cols-2 gap-3 ps-0 md:gap-4 lg:grid-cols-4">
                @foreach ($section->items as $item)
                    <li>
                        <a href="{{ $item->url }}"
                           class="flex h-full flex-col rounded-note border border-line bg-surface p-4.5 no-underline
                                  hover:border-primary-line hover:no-underline md:p-5.5">
                            <span class="flex items-center justify-between gap-2">
                                @if ($item->icon)
                                    <span class="flex size-11 items-center justify-center rounded-md bg-primary-soft text-primary">
                                        <x-icon :name="$item->icon" :size="20" />
                                    </span>
                                @endif
                                @if ($item->kicker !== '')
                                    <span @class(['text-note', 'text-muted' => $item->icon, 'font-bold text-primary' => ! $item->icon])>{{ $item->kicker }}</span>
                                @endif
                            </span>

                            <h3 class="mt-3 text-copy font-bold text-ink md:text-h4">{{ $item->title }}</h3>

                            @if ($item->summary !== '')
                                <p class="mt-1.5 hidden text-note text-muted md:block">{{ Str::limit($item->summary, 120) }}</p>
                            @endif

                            @if ($item->meta)
                                <span class="mt-auto hidden pt-4 text-note font-semibold text-muted md:block">{{ $item->meta }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach

                @if ($section->feature)
                    <li class="col-span-2 lg:col-span-1">
                        <a href="{{ $section->feature->url }}"
                           class="flex h-full flex-col gap-2 rounded-note bg-primary p-4.5 text-on-primary no-underline
                                  hover:bg-primary-deep hover:text-on-primary hover:no-underline md:p-5.5">
                            @if ($section->feature->icon)
                                <span class="flex size-11 items-center justify-center rounded-md bg-primary-deep">
                                    <x-icon :name="$section->feature->icon" :size="20" />
                                </span>
                            @endif
                            <h3 class="mt-1 text-h4">{{ $section->feature->title }}</h3>
                            <p class="text-note text-primary-soft">{{ $section->feature->summary }}</p>
                            <span class="mt-auto pt-2 text-label font-bold">{{ $section->feature->kicker }} ←</span>
                        </a>
                    </li>
                @endif
            </ul>
    @endswitch
</section>
