<x-layouts.public :seo="$seo" active="project-market">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['بازار پروژه', null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="market-index" title="بازار پروژه بهداشت حرفه‌ای و HSE"
                   lede="کارفرما کار را تعریف می‌کند، مشاوران و آزمایشگاه‌های تأییدشده پیشنهاد می‌دهند و پول هر مرحله تا تحویل در امانت فرابهداشت می‌ماند. تعریف پروژه و دادن پیشنهاد رایگان است." />

    <x-page-help topic="market" class="mt-5" />

    <div class="mt-8 flex flex-wrap items-end justify-between gap-4">
        @include('marketplace::market._filter')
        @if (Route::has('market.client.create'))
            <x-button :href="route('market.client.create')" variant="primary" icon="plus">تعریف پروژه</x-button>
        @endif
    </div>

    @if ($service === null && $city === null && ($serviceCounts !== [] || $cityCounts !== []))
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            @if ($serviceCounts !== [])
                <section aria-labelledby="services-heading">
                    <h2 id="services-heading" class="text-h4 text-ink">نوع کار</h2>
                    <ul class="mt-3 flex list-none flex-wrap gap-2 ps-0">
                        @foreach ($serviceCounts as $key => $count)
                            <li>
                                <a href="{{ route('market.service', $key) }}"
                                   class="inline-flex min-h-touch items-center gap-2 rounded-full border border-line bg-surface px-4 text-label text-ink no-underline hover:bg-surface-2 hover:no-underline">
                                    {{ $catalog->serviceName($key) }} <span class="text-note text-muted">@fa($count)</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
            @if ($cityCounts !== [])
                <section aria-labelledby="cities-heading">
                    <h2 id="cities-heading" class="text-h4 text-ink">شهرها</h2>
                    <ul class="mt-3 flex list-none flex-wrap gap-2 ps-0">
                        @foreach ($cityCounts as $key => $count)
                            <li>
                                <a href="{{ route('market.city', $key) }}"
                                   class="inline-flex min-h-touch items-center gap-2 rounded-full border border-line bg-surface px-4 text-label text-ink no-underline hover:bg-surface-2 hover:no-underline">
                                    {{ $catalog->cityName($key) }} <span class="text-note text-muted">@fa($count)</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    @endif

    <div class="mt-8">
        @if ($projects->isEmpty())
            <x-empty-state art="empty-market-index" icon="briefcase"
                           :title="$service || $city ? 'پروژه بازی با این پالایش نیست' : 'هنوز پروژه بازی منتشر نشده'"
                           description="پالایش را بردارید یا بعداً سر بزنید. اگر کاری برای مشاور یا آزمایشگاه دارید، همین حالا رایگان تعریفش کنید.">
                <x-slot:action>
                    <x-button :href="route('market.index')" variant="secondary">همه پروژه‌ها</x-button>
                </x-slot:action>
            </x-empty-state>
        @else
            <ul class="grid list-none gap-4 ps-0 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($projects as $project)
                    @include('marketplace::market._card')
                @endforeach
            </ul>
            <div class="mt-6">{{ $projects->links() }}</div>
        @endif
    </div>

    <x-disclaimer class="mt-10">
        متن هر پروژه را کارفرما نوشته و مدیر فرابهداشت پیش از انتشار بازبینی‌اش کرده است. شماره تماس از راه سایت رد و بدل نمی‌شود؛
        گفت‌وگو، پرداخت و تحویل درون سایت می‌ماند تا پول هر مرحله در امانت محفوظ باشد.
    </x-disclaimer>

</x-layouts.public>
