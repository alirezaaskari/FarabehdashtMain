<x-layouts.public :seo="$seo" active="jobs">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['کاریابی', null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="jobs-index" title="آگهی‌های استخدام بهداشت حرفه‌ای و HSE"
                   lede="آگهی کارفرماهایی که صفحه شرکتشان در فرابهداشت تأیید شده و هر آگهی پیش از انتشار بازبینی شده است. دیدن آگهی و ارسال درخواست برای کارجو همیشه رایگان است." />

    <x-page-help topic="jobs" class="mt-5" />

    @if (Route::has('market.index'))
        <p class="mt-4 text-copy text-body">
            کار پروژه‌ای و کوتاه‌مدت مثل اندازه‌گیری یا ارزیابی ریسک دارید؟
            <a href="{{ route('market.index') }}" class="inline-flex min-h-touch items-center underline">بازار پروژه</a> را ببینید؛ پول هر مرحله تا تحویل در امانت می‌ماند.
        </p>
    @endif

    <div class="mt-8">
        @include('jobs::postings._filter')
    </div>

    @if ($city === null && $skill === null && $type === null && ($cityCounts !== [] || $skillCounts !== []))
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            @if ($cityCounts !== [])
                <section aria-labelledby="cities-heading">
                    <h2 id="cities-heading" class="text-h4 text-ink">شهرها</h2>
                    <ul class="mt-3 flex list-none flex-wrap gap-2 ps-0">
                        @foreach ($cityCounts as $key => $count)
                            <li>
                                <a href="{{ route('jobs.city', $key) }}"
                                   class="inline-flex min-h-touch items-center gap-2 rounded-full border border-line bg-surface px-4 text-label text-ink no-underline hover:bg-surface-2 hover:no-underline">
                                    {{ $catalog->cityName($key) }} <span class="text-note text-muted">@fa($count)</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
            @if ($skillCounts !== [])
                <section aria-labelledby="skills-heading">
                    <h2 id="skills-heading" class="text-h4 text-ink">مهارت‌های پرتقاضا</h2>
                    <ul class="mt-3 flex list-none flex-wrap gap-2 ps-0">
                        @foreach ($skillCounts as $slug => $count)
                            <li>
                                <a href="{{ route('jobs.skill', $slug) }}"
                                   class="inline-flex min-h-touch items-center gap-2 rounded-full border border-line bg-surface px-4 text-label text-ink no-underline hover:bg-surface-2 hover:no-underline">
                                    {{ $catalog->skill($slug)?->name }} <span class="text-note text-muted">@fa($count)</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    @endif

    <div class="mt-8">
        @if ($postings->isEmpty())
            <x-empty-state art="empty-jobs-index" icon="briefcase"
                           :title="$city || $skill || $type ? 'آگهی‌ای با این پالایش نیست' : 'هنوز آگهی‌ای منتشر نشده'"
                           description="پالایش را بردارید یا بعداً سر بزنید. اگر کارفرمایید، نقش کارفرما را از «نقش‌ها و پروفایل‌ها» درخواست کنید.">
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
        متن هر آگهی را کارفرما نوشته و مدیر فرابهداشت پیش از انتشار بازبینی‌اش کرده است. اگر کسی برای استخدام از شما پول خواست، آگهی را گزارش کنید؛ کارجو در فرابهداشت هیچ‌وقت پولی نمی‌دهد.
    </x-disclaimer>

</x-layouts.public>
