@php use App\Support\JalaliDate; @endphp

<x-layouts.public :seo="$seo" active="jobs">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['کاریابی', route('jobs.index')], [$posting->title, null]]" />
    </x-slot:breadcrumb>

    <article>
        <header>
            @unless ($live)
                <x-alert tone="caution" class="mb-6" :title="$posting->closed_at ? 'این آگهی بسته شده است' : 'مهلت این آگهی تمام شده است'">
                    آگهی دیگر درخواست نمی‌پذیرد و فقط برای مراجعه این‌جا مانده است. آگهی‌های زنده را در فهرست کاریابی ببینید.
                </x-alert>
            @endunless
            <h1 class="text-h1 text-ink">{{ $posting->title }}</h1>
            <p class="mt-2 text-lede text-body">
                <a href="{{ route('jobs.companies.show', $company->slug) }}">{{ $company->name }}</a>
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                @if ($place !== '')
                    <x-badge icon="compass">{{ $place }}</x-badge>
                @endif
                <x-badge>{{ $posting->employment_type?->label() }}</x-badge>
                <x-badge :tone="$live ? 'primary' : 'danger'">{{ $posting->state()->label() }}</x-badge>
            </div>
        </header>

        <div class="mt-10 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0">
                <section aria-labelledby="description-heading">
                    <h2 id="description-heading" class="text-h2 text-ink">شرح شغل</h2>
                    @foreach ($posting->descriptionParagraphs() as $paragraph)
                        <p class="mt-4 whitespace-pre-line text-copy text-body">{{ $paragraph }}</p>
                    @endforeach
                </section>

                @if ($skills !== [])
                    <section aria-labelledby="skills-heading" class="mt-10">
                        <h2 id="skills-heading" class="text-h3 text-ink">مهارت‌های لازم</h2>
                        <ul class="mt-4 flex list-none flex-wrap gap-2 ps-0">
                            @foreach ($skills as $term)
                                <li>
                                    <a href="{{ route('jobs.skill', $term->slug) }}"
                                       class="inline-flex min-h-touch items-center rounded-full border border-line bg-surface px-4 text-label text-ink no-underline hover:bg-surface-2 hover:no-underline">{{ $term->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($more->isNotEmpty())
                    <section aria-labelledby="more-heading" class="mt-10">
                        <h2 id="more-heading" class="text-h3 text-ink">آگهی‌های دیگر {{ $company->name }}</h2>
                        <ul class="mt-4 grid list-none gap-4 ps-0 sm:grid-cols-2">
                            @foreach ($more as $other)
                                @include('jobs::postings._card', ['posting' => $other])
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="flex flex-col gap-5">
                <x-card title="خلاصه" heading="text-h4">
                    <x-art name="job-show" class="mb-4 h-24 w-auto" />
                    <dl class="flex flex-col gap-3 text-copy">
                        <div><dt class="text-note text-muted">حقوق</dt><dd class="text-ink">{{ $catalog->salaryLabel($posting) }}</dd></div>
                        <div><dt class="text-note text-muted">سابقه</dt><dd class="text-ink">{{ $catalog->experienceLabel($posting->min_experience_years) }}</dd></div>
                        <div><dt class="text-note text-muted">انتشار</dt><dd class="text-ink">{{ JalaliDate::long($posting->published_at) }}</dd></div>
                        @if ($live && $posting->expires_at)
                            <div><dt class="text-note text-muted">مهلت</dt><dd class="text-ink">{{ JalaliDate::long($posting->expires_at) }}</dd></div>
                        @endif
                    </dl>
                    @if ($live && Route::has('jobs.apply.create'))
                        <x-button :href="route('jobs.apply.create', $posting->id)" variant="primary" block class="mt-5">ارسال درخواست رایگان</x-button>
                    @endif
                </x-card>

                <x-card :title="$company->name" heading="text-h4">
                    <div class="flex items-center gap-3">
                        @if ($logo)
                            <img src="{{ $logo->url }}" alt="نشان {{ $company->name }}" width="48" height="48" class="size-12 shrink-0 rounded-md object-cover">
                        @endif
                        <p class="text-copy text-body">{{ $company->industry }} · {{ $company->size?->label() }}</p>
                    </div>
                    <x-button :href="route('jobs.companies.show', $company->slug)" variant="secondary" size="sm" class="mt-4">صفحه شرکت</x-button>
                </x-card>
            </aside>
        </div>
    </article>

    <x-disclaimer class="mt-10">
        متن آگهی را کارفرما نوشته و مدیر فرابهداشت پیش از انتشار بازبینی‌اش کرده است. کارجو در فرابهداشت هیچ‌وقت پولی نمی‌دهد؛ اگر کسی برای استخدام از شما پول خواست، آن را گزارش کنید.
    </x-disclaimer>

</x-layouts.public>
