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

                <section aria-labelledby="services-heading" class="mt-10">
                    <h2 id="services-heading" class="text-h3 text-ink">خدمت‌ها</h2>
                    @if ($services->isEmpty())
                        <p class="mt-3 text-copy text-muted">این مشاور هنوز خدمتی برای خرید تعریف نکرده است.</p>
                    @else
                        <ul class="mt-4 flex list-none flex-col gap-3 ps-0">
                            @foreach ($services as $service)
                                <li class="rounded-xl border border-line bg-surface px-5 py-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="text-h4 text-ink">{{ $service->title }}</h3>
                                            <p class="mt-1 flex flex-wrap items-center gap-2 text-note text-muted">
                                                <span>{{ $service->kind->label() }}</span>
                                                @if ($service->duration_minutes)
                                                    <span aria-hidden="true">·</span>
                                                    <span>@fa($service->duration_minutes) دقیقه</span>
                                                @endif
                                                @if ($service->kind->value === 'visit')
                                                    <span aria-hidden="true">·</span>
                                                    <span>{{ implode('، ', array_map(fn ($city) => $regions->cityName($city), $service->cities ?? [])) }}</span>
                                                @endif
                                            </p>
                                        </div>
                                        <p class="text-h4 text-ink">{{ $service->price()->format() }}</p>
                                    </div>
                                    @foreach ($service->paragraphs() as $paragraph)
                                        <p class="mt-3 text-copy text-body">{{ $paragraph }}</p>
                                    @endforeach
                                    @if ($salesOpen[$service->kind->value] ?? false)
                                        <div class="mt-4">
                                            <x-button :href="route('consulting.orders.create', $service->uuid)" variant="primary">درخواست این خدمت</x-button>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-note text-muted">
                            مبلغ تا پایان کار نزد فرابهداشت امانت می‌ماند؛ اگر مشاور نپذیرد یا @fa((int) config('consulting.orders.reply_hours', 48)) ساعت پاسخ ندهد، کامل به کیف پول شما برمی‌گردد.
                        </p>
                    @endif
                </section>

                @if ($offerings !== [])
                    <section aria-labelledby="offerings-heading" class="mt-10">
                        <h2 id="offerings-heading" class="text-h3 text-ink">در فهرست خدمات تخصصی</h2>
                        <ul class="mt-3 flex list-none flex-wrap gap-2 ps-0">
                            @foreach ($offerings as $key => $name)
                                <li>
                                    <a href="{{ route('consulting.directory.service', $key) }}"
                                       class="inline-flex min-h-touch items-center rounded-full border border-line bg-surface px-4 text-label text-ink no-underline hover:bg-surface-2 hover:no-underline">{{ $name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

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
                @if ($marketRecord)
                    @include('consulting::_market-record', ['record' => $marketRecord])
                @endif
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
                        شماره تماس و ایمیل مشاوران روی سایت نمی‌آید. برای کار با این مشاور یکی از خدمت‌هایش را درخواست کنید؛ گفت‌وگو در صفحه همان درخواست است. پرسش کوتاه را در «پرسش از متخصص» بپرسید.
                    </p>
                    @if (Route::has('expert.create'))
                        <div class="mt-4">
                            <x-button :href="route('expert.create')" variant="primary">پرسش تازه</x-button>
                        </div>
                    @endif
                </x-card>
                @if (Route::has('market.invite.create') && auth()->id() !== $profile->user_id)
                    <x-card title="پروژه دارید؟" heading="text-h4">
                        <p class="text-copy text-body">
                            کار چندمرحله‌ای را در بازار پروژه تعریف کنید و این مشاور را دعوت کنید تا با مبلغ و زمان هر مرحله پیشنهاد بدهد؛ پول هر مرحله تا تحویل در امانت می‌ماند.
                        </p>
                        <div class="mt-4">
                            <x-button :href="route('market.invite.create', $profile->user_id)" variant="secondary">دعوت به پروژه</x-button>
                        </div>
                    </x-card>
                @endif
            </aside>
        </div>

        <x-disclaimer class="mt-10">
            متن این صفحه را خود مشاور نوشته و مدیر فرابهداشت پیش از انتشار بازبینی‌اش کرده است. سابقه و تحصیلات به اظهار مشاور است؛ این صفحه گواهی یا مدرک رسمی نیست.
        </x-disclaimer>
    </article>

</x-layouts.public>
