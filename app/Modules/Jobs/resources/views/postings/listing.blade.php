<x-layouts.public :seo="$seo" active="jobs">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['کاریابی', route('jobs.index')], [$title, null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="jobs-listing" :title="$title" :lede="$lede" />

    <div class="mt-8">
        @include('jobs::postings._filter', ['type' => null])
    </div>

    <div class="mt-8">
        @if ($postings->isEmpty())
            <x-empty-state art="empty-jobs-listing" icon="briefcase" title="فعلاً آگهی زنده‌ای این‌جا نیست"
                           description="آگهی‌ها سی روزه‌اند و زود عوض می‌شوند؛ همه آگهی‌ها را ببینید یا بعداً سر بزنید.">
                <x-slot:action>
                    <x-button :href="route('jobs.index')" variant="primary">همه آگهی‌ها</x-button>
                </x-slot:action>
            </x-empty-state>
        @else
            <ul class="grid list-none gap-4 ps-0 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($postings as $posting)
                    @include('jobs::postings._card')
                @endforeach
            </ul>
            <div class="mt-6">{{ $postings->links() }}</div>
        @endif
    </div>

    <x-disclaimer class="mt-10">
        متن هر آگهی را کارفرما نوشته و مدیر فرابهداشت پیش از انتشار بازبینی‌اش کرده است. کارجو در فرابهداشت هیچ‌وقت برای دیدن آگهی یا فرستادن درخواست پولی نمی‌دهد.
    </x-disclaimer>

</x-layouts.public>
