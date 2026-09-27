<x-layouts.public :seo="$seo" active="jobs">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['کاریابی', route('jobs.index')], [$company->name, null]]" />
    </x-slot:breadcrumb>

    <article>
        <header class="flex flex-wrap items-center gap-5">
            @if ($logo)
                <img src="{{ $logo->url }}" alt="نشان {{ $company->name }}" width="96" height="96" class="size-24 shrink-0 rounded-lg object-cover">
            @else
                <span aria-hidden="true" class="flex size-24 shrink-0 items-center justify-center rounded-lg bg-primary-soft text-display text-on-primary-soft">{{ mb_substr((string) $company->name, 0, 1) }}</span>
            @endif
            <div class="min-w-0">
                <h1 class="text-h1 text-ink">{{ $company->name }}</h1>
                <p class="mt-1 text-lede text-body">{{ $company->industry }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-badge tone="primary" icon="shield">کارفرمای تأییدشده در فرابهداشت</x-badge>
                    @if ($place !== '')
                        <x-badge icon="compass">{{ $place }}</x-badge>
                    @endif
                    @if ($company->size)
                        <x-badge icon="user">{{ $company->size->label() }}</x-badge>
                    @endif
                </div>
            </div>
        </header>

        <div class="mt-10 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section aria-labelledby="about-heading" class="min-w-0">
                <h2 id="about-heading" class="text-h2 text-ink">درباره شرکت</h2>
                <p class="mt-1 text-note text-muted">به اظهار کارفرما</p>
                @foreach ($company->aboutParagraphs() as $paragraph)
                    <p class="mt-4 text-copy text-body">{{ $paragraph }}</p>
                @endforeach
            </section>

            <x-card title="آگهی‌های باز" heading="text-h4">
                <x-art name="company-show" class="mb-4 h-24 w-auto" />
                @if ($postings->isEmpty())
                    <p class="text-copy text-muted">این شرکت فعلاً آگهی بازی ندارد.</p>
                @else
                    <ul class="flex list-none flex-col gap-3 ps-0">
                        @foreach ($postings as $posting)
                            <li>
                                <a href="{{ route('jobs.show', $posting->id) }}" class="text-label font-semibold">{{ $posting->title }}</a>
                                <p class="text-note text-muted">{{ $catalog->place($posting->province, $posting->city) }} · {{ $posting->employment_type?->label() }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </article>

    <x-disclaimer class="mt-10">
        «کارفرمای تأییدشده در فرابهداشت» یعنی مدیر سایت مدرک ثبت یا معرفی‌نامه شرکت را دیده است؛ مجوز یا گواهی رسمی نیست. شماره و ایمیل شرکت روی سایت نمی‌آید.
    </x-disclaimer>

</x-layouts.public>
