@php use App\Support\JalaliDate; @endphp

<x-layouts.public :seo="$seo" active="consultants">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['مشاوران', route('consulting.index')], [$profile->display_name, null]]" />
    </x-slot:breadcrumb>

    <article>
        <header class="flex flex-wrap items-center gap-5">
            @if ($photo)
                <img src="{{ $photo->url }}" alt="عکس {{ $profile->display_name }}" width="112" height="112"
                     class="size-28 shrink-0 rounded-full object-cover">
            @else
                <span aria-hidden="true" class="flex size-28 shrink-0 items-center justify-center rounded-full bg-primary-soft text-display text-on-primary-soft">{{ mb_substr((string) $profile->display_name, 0, 1) }}</span>
            @endif
            <div class="min-w-0">
                <h1 class="text-h1 text-ink">{{ $profile->display_name }}</h1>
                <p class="mt-1 text-lede text-body">{{ $profile->headline }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-badge tone="primary" icon="shield">مشاور تأییدشده در فرابهداشت</x-badge>
                    @if ($place !== '')
                        <x-badge icon="compass">{{ $place }}</x-badge>
                    @endif
                </div>
            </div>
        </header>

        <div class="mt-10 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0">
                <section aria-labelledby="bio-heading">
                    <h2 id="bio-heading" class="text-h2 text-ink">معرفی</h2>
                    @foreach ($profile->bioParagraphs() as $paragraph)
                        <p class="mt-4 text-copy text-body">{{ $paragraph }}</p>
                    @endforeach
                </section>

                @if ($domains !== [])
                    <section aria-labelledby="domains-heading" class="mt-10">
                        <h2 id="domains-heading" class="text-h3 text-ink">حوزه‌های تخصص</h2>
                        <ul class="mt-3 flex list-none flex-wrap gap-2 ps-0">
                            @foreach ($domains as $term)
                                <li><x-badge>{{ $term->name }}</x-badge></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section aria-labelledby="answers-heading" class="mt-10">
                    <h2 id="answers-heading" class="text-h3 text-ink">
                        @if ($answerCount > 0) @fa($answerCount) پاسخ در «پرسش از متخصص» @else پاسخ‌ها در «پرسش از متخصص» @endif
                    </h2>
                    @if ($answers === [])
                        <div class="mt-4">
                            <x-empty-state art="empty-consultant-answers" icon="bulb"
                                           title="هنوز پاسخ منتشرشده‌ای ندارد"
                                           description="پاسخ‌هایی که این مشاور به پرسش‌های عمومی می‌دهد، پس از تأیید مدیر این‌جا فهرست می‌شود." />
                        </div>
                    @else
                        <ul class="mt-4 flex list-none flex-col gap-3 ps-0">
                            @foreach ($answers as $answer)
                                <li>
                                    <a href="{{ $answer->url }}"
                                       class="flex min-h-touch flex-col gap-1 rounded-xl border border-line bg-surface px-5 py-4 no-underline hover:bg-surface-2 hover:no-underline">
                                        <span class="text-h4 text-ink">{{ $answer->questionTitle }}</span>
                                        <span class="flex flex-wrap items-center gap-2 text-note text-muted">
                                            <span>{{ JalaliDate::short($answer->publishedAt) }}</span>
                                            @if ($answer->accepted)
                                                <span aria-hidden="true">·</span>
                                                <span>بهترین پاسخ به انتخاب پرسش‌کننده</span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            <aside class="flex flex-col gap-6">
                @if ($profile->experience)
                    <x-card title="سابقه کار" heading="text-h4">
                        <p class="text-note text-muted">به اظهار مشاور</p>
                        <p class="mt-2 whitespace-pre-line text-copy text-body">{{ $profile->experience }}</p>
                    </x-card>
                @endif
                @if ($profile->education)
                    <x-card title="تحصیلات" heading="text-h4">
                        <p class="text-note text-muted">به اظهار مشاور</p>
                        <p class="mt-2 whitespace-pre-line text-copy text-body">{{ $profile->education }}</p>
                    </x-card>
                @endif
                <x-card title="تماس با مشاور" heading="text-h4">
                    <p class="text-copy text-body">
                        شماره تماس و ایمیل مشاوران روی سایت نمی‌آید. پرسش تخصصی‌تان را در «پرسش از متخصص» بپرسید؛ مشاوران تأییدشده، از جمله همین مشاور، پاسخ می‌دهند.
                    </p>
                    @if (Route::has('expert.create'))
                        <div class="mt-4">
                            <x-button :href="route('expert.create')" variant="primary">پرسش تازه</x-button>
                        </div>
                    @endif
                </x-card>
            </aside>
        </div>

        <x-disclaimer class="mt-10">
            متن این صفحه را خود مشاور نوشته و مدیر فرابهداشت پیش از انتشار بازبینی‌اش کرده است. سابقه و تحصیلات به اظهار مشاور است؛ این صفحه گواهی یا مدرک رسمی نیست.
        </x-disclaimer>
    </article>

</x-layouts.public>
