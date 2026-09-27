<x-layouts.public :seo="$seo" active="jobs">

    <article class="flex flex-col gap-10">
        <header class="flex flex-wrap items-center gap-6">
            <x-art name="passport-public" class="h-32 w-auto" />
            <div class="min-w-0">
                <p class="text-label text-muted">گذرنامه مهارتی</p>
                <h1 class="text-h1 text-ink">{{ $passport->user->name ?: 'کاربر فرابهداشت' }}</h1>
                @if ($passport->headline)
                    <p class="mt-2 text-lede text-body">{{ $passport->headline }}</p>
                @endif
                <p class="mt-2 text-label text-muted">
                    @if ($passport->city) {{ $catalog->cityName($passport->city) }} @endif
                    @if ($passport->city && $passport->experience_years !== null) · @endif
                    @if ($passport->experience_years !== null) @fa($passport->experience_years) سال سابقه @endif
                </p>
            </div>
        </header>

        @include('jobs::passport._verified')

        <section aria-labelledby="declared-heading" class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <h2 id="declared-heading" class="text-h3 text-ink">به اظهار خود کاربر</h2>
                <x-badge tone="neutral">بدون تأیید</x-badge>
            </div>

            @if ($declaredSkills !== [])
                <ul class="flex list-none flex-wrap gap-2 ps-0">
                    @foreach ($declaredSkills as $term)
                        <li><x-badge tone="neutral">{{ $term->name }}</x-badge></li>
                    @endforeach
                </ul>
            @endif

            @include('jobs::passport._declared')

            @if ($declaredSkills === [] && $passport->entries->isEmpty())
                <p class="text-copy text-muted">چیزی اظهار نشده است.</p>
            @endif
        </section>

        <x-disclaimer>این گذرنامه گواهی یا مدرک رسمی نیست. بخش «ثبت‌شده» فقط کارهایی است که در فرابهداشت انجام شده و بخش «به اظهار خود کاربر» بررسی نمی‌شود.</x-disclaimer>
    </article>

</x-layouts.public>
