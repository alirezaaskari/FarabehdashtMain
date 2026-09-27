<x-layouts.public title="بسته‌های راه‌حل"
                  description="چند فایل، دوره و اشتراک حرفه‌ای که با هم یک کار را حل می‌کنند، در یک خرید و با قیمتی کمتر از جمع اجزا."
                  :canonical="route('bundles.index')"
                  active="bundles">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['بسته‌های راه‌حل', null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="bundles-index" title="بسته‌های راه‌حل"
                   lede="هر بسته چیزهایی را کنار هم می‌گذارد که برای یک کار مشخص لازم دارید؛ مثلاً فرم اندازه‌گیری، دوره آموزشی و چند ماه اشتراک حرفه‌ای." />

    <x-page-help topic="bundles" class="mt-5" />

    <div class="mt-8">
        @if ($rows === [])
            <x-empty-state art="empty-bundles-index" icon="list"
                           title="هنوز بسته‌ای منتشر نشده"
                           description="بسته‌های راه‌حل به‌زودی این‌جا می‌آیند." />
        @else
            <ul class="flex list-none flex-col divide-y divide-line border-y border-line ps-0">
                @foreach ($rows as $row)
                    <li>
                        <a href="{{ route('bundles.show', $row['bundle']->slug) }}"
                           class="flex min-h-touch flex-col gap-1 py-5 no-underline hover:no-underline md:flex-row md:items-center md:justify-between md:gap-6">
                            <span class="min-w-0">
                                <span class="block text-h4 text-ink">{{ $row['bundle']->title }}</span>
                                <span class="mt-1 block text-note text-muted">@fa($row['bundle']->items->count()) جزء</span>
                            </span>
                            <span class="flex shrink-0 items-baseline gap-3">
                                @if ($row['listTotal']->isGreaterThan($row['bundle']->price()))
                                    <s class="text-note text-muted">{{ $row['listTotal']->format() }}</s>
                                @endif
                                <span class="text-label font-semibold text-ink">{{ $row['bundle']->price()->format() }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

</x-layouts.public>
