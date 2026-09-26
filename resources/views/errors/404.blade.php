{{--
    ۴۰۴ هرگز بن‌بست نیست: جعبه جست‌وجو دارد (docs/architecture/data-freshness-and-print.md).
    در سایتی با هزاران صفحه CAS این صفحه پرترافیک است.
--}}

@php
    $suggestions = array_values(array_filter([
        Route::has('tools.advisor') ? ['دستیار انتخاب ابزار', route('tools.advisor'), 'compass'] : null,
        Route::has('tools.index') ? ['ابزارهای محاسبه', route('tools.index'), 'calculator'] : null,
        Route::has('chemicals.index') ? ['بانک مواد شیمیایی', route('chemicals.index'), 'chemical'] : null,
        Route::has('encyclopedia.index') ? ['دانشنامه', route('encyclopedia.index'), 'book'] : null,
    ]));
@endphp

<x-errors.layout art="errors-404"
    :shell="true"
    code="404"
    title="این صفحه پیدا نشد"
    message="نشانی اشتباه تایپ شده یا این صفحه جابه‌جا شده است. جست‌وجو کنید یا از صفحه اصلی دوباره شروع کنید.">
    @if (Route::has('workspace.search'))
        <form method="GET" action="{{ route('workspace.search') }}" role="search" class="flex w-full gap-2.5">
            <label for="not-found-search" class="sr-only">جست‌وجو در فرابهداشت</label>
            <input id="not-found-search" type="search" name="q"
                   placeholder="نام ماده، ابزار یا موضوع"
                   class="h-field min-w-0 grow rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
            <x-button type="submit" variant="secondary" icon="search" class="shrink-0">جست‌وجو</x-button>
        </form>
    @endif

    @if ($suggestions !== [])
        <nav aria-labelledby="not-found-suggestions" class="w-full">
            <h2 id="not-found-suggestions" class="text-label font-semibold text-muted">شاید دنبال یکی از این‌ها بودید</h2>
            <ul class="mt-3 grid list-none gap-2.5 ps-0 sm:grid-cols-2">
                @foreach ($suggestions as [$label, $url, $icon])
                    <li>
                        <a href="{{ $url }}"
                           class="flex min-h-touch items-center gap-3 rounded-lg border border-line bg-surface px-4 py-3
                                  text-label font-semibold text-ink no-underline hover:border-primary-line hover:no-underline">
                            <span class="text-primary"><x-icon :name="$icon" :size="18" /></span>
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
</x-errors.layout>
