@php $photos = $presenter->photos($providers->items()); @endphp

<x-layouts.public :seo="$seo" active="directory">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['خدمات تخصصی', null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="directory-index" title="خدمات تخصصی بهداشت حرفه‌ای"
                   lede="مشاوران و آزمایشگاه‌هایی که نقششان در فرابهداشت تأیید شده و صفحه‌شان پیش از انتشار بازبینی شده است، به تفکیک خدمت و شهر." />

    <x-page-help topic="directory" class="mt-5" />

    <div class="mt-8">
        @include('consulting::directory._filter', ['service' => null])
    </div>

    <section aria-labelledby="services-heading" class="mt-10">
        <h2 id="services-heading" class="text-h3 text-ink">خدمت‌ها</h2>
        <ul class="mt-4 grid list-none gap-3 ps-0 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($services as $key => $name)
                <li>
                    <a href="{{ route('consulting.directory.service', $key) }}"
                       class="flex min-h-touch items-center justify-between gap-3 rounded-xl border border-line bg-surface px-5 py-3 no-underline hover:bg-surface-2 hover:no-underline">
                        <span class="text-label text-ink">{{ $name }}</span>
                        <span class="text-note text-muted">@fa($counts[$key] ?? 0) ارائه‌دهنده</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="providers-heading" class="mt-10">
        <h2 id="providers-heading" class="text-h3 text-ink">
            {{ $city ? 'ارائه‌دهندگان در '.$catalog->cityName($city) : 'همه ارائه‌دهندگان' }}
        </h2>
        <div class="mt-4">
            @if ($providers->isEmpty())
                <x-empty-state art="empty-directory" icon="user" title="هنوز ارائه‌دهنده‌ای این‌جا نیست"
                               description="مشاور و آزمایشگاهی که نقشش تأیید شده، پس از انتشار صفحه‌اش با خدمت‌ها و شهرش این‌جا می‌آید.">
                    @if (Route::has('identity.profiles'))
                        <x-slot:action>
                            <x-button :href="route('identity.profiles')" variant="primary">درخواست نقش مشاور یا آزمایشگاه</x-button>
                        </x-slot:action>
                    @endif
                </x-empty-state>
            @else
                <ul class="grid list-none gap-4 ps-0 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($providers as $profile)
                        @include('consulting::directory._card')
                    @endforeach
                </ul>
                <div class="mt-6">{{ $providers->withQueryString()->links() }}</div>
            @endif
        </div>
    </section>

</x-layouts.public>
