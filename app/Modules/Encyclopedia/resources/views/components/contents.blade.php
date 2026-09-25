@props(['article', 'tools' => [], 'heading' => true])

{{--
    «در این مقاله» و «ابزارهای مرتبط» — یک بار نوشته، دو جا نشسته: ستون
    کناری دسکتاپ و پنل جمع‌شونده موبایل. heading=false وقتی عنوان را خود
    پنل جمع‌شونده دارد.
--}}

@if ($heading)
    <h2 class="mb-3 text-copy font-extrabold text-ink">در این مقاله</h2>
@endif

<nav aria-label="بخش‌های این مقاله" class="flex flex-col">
    @foreach ($article->sections as $section)
        <a href="#{{ $section->anchor() }}" data-section-link
           class="flex min-h-touch items-center text-label font-semibold text-muted
                  no-underline hover:text-primary hover:no-underline
                  aria-[current=location]:font-extrabold aria-[current=location]:text-primary">
            @fa($section->position). {{ $section->heading }}
        </a>
    @endforeach
</nav>

@if ($tools !== [])
    <div class="mt-5 border-t border-line-soft pt-5">
        <h2 class="mb-3 text-copy font-extrabold text-ink">ابزارهای مرتبط</h2>

        <div class="flex flex-col">
            @foreach ($tools as $tool)
                <a href="{{ route('tools.show', $tool->slug) }}"
                   class="flex min-h-touch items-center gap-2 text-label font-semibold text-primary
                          no-underline hover:no-underline">
                    <x-icon name="calculator" :size="16" />
                    {{ $tool->title }}
                </a>
            @endforeach
        </div>
    </div>
@endif
