@php
    $title = $cityName ? $serviceName.' در '.$cityName : $serviceName;
    $photos = $presenter->photos($providers->items());
@endphp

<x-layouts.public :seo="$seo" active="directory">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="array_values(array_filter([
            ['خانه', Route::has('home') ? route('home') : '/'],
            ['خدمات تخصصی', route('consulting.directory.index')],
            $cityName ? [$serviceName, route('consulting.directory.service', $service)] : null,
            [$title, null],
        ]))" />
    </x-slot:breadcrumb>

    <x-page-header art="directory-listing" :title="$title"
                   :lede="$cityName
                        ? 'مشاوران و آزمایشگاه‌هایی که «'.$serviceName.'» را در '.$cityName.' ارائه می‌دهند. معرفی و خدمت‌های هر کدام را در صفحه‌اش ببینید.'
                        : 'مشاوران و آزمایشگاه‌هایی که «'.$serviceName.'» را ارائه می‌دهند، به تفکیک شهر.'" />
    <x-page-help topic="directory" class="mt-5" />

    <div class="mt-8">
        @include('consulting::directory._filter')
    </div>

    @if ($cities !== [])
        <section aria-labelledby="cities-heading" class="mt-8">
            <h2 id="cities-heading" class="text-h4 text-ink">شهرها</h2>
            <ul class="mt-3 flex list-none flex-wrap gap-2 ps-0">
                @foreach ($cities as $key => $count)
                    <li>
                        <a href="{{ route('consulting.directory.city', [$service, $key]) }}"
                           class="inline-flex min-h-touch items-center gap-2 rounded-full border border-line bg-surface px-4 text-label text-ink no-underline hover:bg-surface-2 hover:no-underline">
                            {{ $catalog->cityName($key) }} <span class="text-note text-muted">@fa($count)</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="mt-8">
        @if ($providers->isEmpty())
            <x-empty-state art="empty-directory-listing" icon="compass" :title="'هنوز ارائه‌دهنده‌ای برای '.$title.' نیست'"
                           description="فهرست همه خدمت‌ها و شهرها را ببینید یا در «پرسش از متخصص» بپرسید.">
                <x-slot:action>
                    <x-button :href="route('consulting.directory.index')" variant="primary">همه خدمات تخصصی</x-button>
                </x-slot:action>
            </x-empty-state>
        @else
            <ul class="grid list-none gap-4 ps-0 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($providers as $profile)
                    @include('consulting::directory._card')
                @endforeach
            </ul>
            <div class="mt-6">{{ $providers->links() }}</div>
        @endif
    </div>

    <x-disclaimer class="mt-10">
        متن هر صفحه را خود ارائه‌دهنده نوشته و مدیر فرابهداشت پیش از انتشار بازبینی‌اش کرده است. فهرست‌شدن در این صفحه گواهی یا مجوز رسمی نیست.
    </x-disclaimer>

</x-layouts.public>
