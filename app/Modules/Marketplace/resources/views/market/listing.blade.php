<x-layouts.public :seo="$seo" active="project-market">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['بازار پروژه', route('market.index')], [$title, null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="market-listing" :title="$title" :lede="$lede" />

    <div class="mt-8">
        @include('marketplace::market._filter')
    </div>

    <div class="mt-8">
        @if ($projects->isEmpty())
            <x-empty-state art="empty-market-listing" icon="briefcase" title="فعلاً پروژه بازی این‌جا نیست"
                           description="پروژه‌ها دو هفته پیشنهاد می‌پذیرند و زود عوض می‌شوند؛ همه پروژه‌ها را ببینید یا بعداً سر بزنید.">
                <x-slot:action>
                    <x-button :href="route('market.index')" variant="primary">همه پروژه‌ها</x-button>
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

</x-layouts.public>
