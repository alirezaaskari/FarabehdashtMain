{{--
    ۴۰۴ هرگز بن‌بست نیست: جعبه جست‌وجو دارد (docs/architecture/data-freshness-and-print.md).
    در سایتی با هزاران صفحه CAS این صفحه پرترافیک است.
--}}

<x-errors.layout
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
</x-errors.layout>
