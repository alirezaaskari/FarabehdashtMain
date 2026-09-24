@php
    use App\Support\JalaliDate;
@endphp

<x-layouts.public :seo="$seo" active="encyclopedia">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['خانه', route('home')],
            ['دانشنامه', route('encyclopedia.index')],
            [$article->type->label(), route('encyclopedia.index', ['type' => [$article->type->value]])],
            [$article->title, null],
        ]" />
    </x-slot:breadcrumb>

    <div class="flex flex-col gap-8 lg:flex-row lg:items-start">

        {{-- فهرست «در این مقاله» از خود بخش‌ها ساخته می‌شود، نه از تجزیه متن.
             روی موبایل همین فهرست جمع‌شده زیر مشخصات مقاله می‌آید، نه پیش از عنوان. --}}
        <aside data-print="hide"
               class="hidden w-[15.625rem] shrink-0 rounded-xl border border-line bg-surface p-6 lg:sticky lg:top-6 lg:block">
            <x-encyclopedia::contents :article="$article" :tools="$tools" />

            {{-- همین پیوندها درون متن هم هستند؛ روی موبایل که این ستون پنهان است
                 تکرارشان فقط متن را پایین‌تر می‌برد. --}}
            @if ($mentions !== [])
                <div class="mt-5 border-t border-line-soft pt-5">
                    <h2 class="mb-3 text-copy font-extrabold text-ink">در این مطلب</h2>

                    <div class="flex flex-col">
                        @foreach ($mentions as $mention)
                            <a href="{{ $mention->url }}"
                               class="flex min-h-touch items-center gap-2 text-label font-semibold text-primary
                                      no-underline hover:no-underline">
                                <x-icon :name="$mention->kind() === 'tools' ? 'calculator' : 'chemical'" :size="16" />
                                {{ $mention->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>

        <article class="min-w-0 grow">
            <span class="text-note font-bold text-caution">{{ $article->type->label() }}</span>

            <h1 class="mt-2 text-display text-ink">{{ $article->title }}</h1>

            <p class="mt-4 max-w-[46rem] text-lede text-muted">{{ $article->summary }}</p>

            <x-encyclopedia::freshness-bar class="mt-6"
                                           :article="$article"
                                           :freshness="$freshness"
                                           :daysUntilDue="$daysUntilDue" />

            {{-- نویسنده و بازبین همیشه با هم و همیشه با تاریخ: نامی بدون تاریخ
                 نمی‌گوید کِی، و تاریخی بدون نام نمی‌گوید چه کسی پایش ایستاده. --}}
            <dl class="mt-5 grid grid-cols-2 gap-4 rounded-xl border border-line bg-surface px-6 py-5 xl:grid-cols-4">
                @foreach ([
                    ['نویسنده', $article->author?->name ?? 'ثبت نشده'],
                    ['بازبین علمی', $article->reviewer?->name ?? 'ثبت نشده'],
                    ['آخرین بازبینی', $article->reviewed_at ? JalaliDate::short($article->reviewed_at) : 'ثبت نشده'],
                    ['بازبینی بعدی', $article->review_due_at ? JalaliDate::short($article->review_due_at) : 'ثبت نشده'],
                ] as [$label, $value])
                    <div>
                        <dt class="text-note text-muted">{{ $label }}</dt>
                        <dd class="mt-1 text-label font-bold text-ink">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            <x-disclosure open-from="lg" data-print="hide"
                          class="mt-5 rounded-xl border border-line bg-surface px-6 py-2 lg:hidden">
                <x-slot:summary>
                    <h2 class="text-copy font-extrabold text-ink">در این مقاله</h2>
                </x-slot:summary>

                <div class="pb-4">
                    <x-encyclopedia::contents :article="$article" :tools="$tools" :heading="false" />
                </div>
            </x-disclosure>

            @foreach ($article->sections as $section)
                <section aria-labelledby="{{ $section->anchor() }}">
                    <h2 id="{{ $section->anchor() }}" class="mt-9 text-h2 text-ink">
                        @fa($section->position). {{ $section->heading }}
                    </h2>

                    @foreach ($body[$section->id] ?? [] as $segments)
                        <p class="mt-4 max-w-[46rem] text-lede text-body"><x-linked-text :segments="$segments" /></p>
                    @endforeach

                    @if ($section->note)
                        <div class="mt-5 max-w-[46rem] rounded-e-lg border-s-[3px] border-primary bg-surface-2 px-5 py-4">
                            <h3 class="text-copy font-bold text-primary-deep">نکته کلیدی</h3>
                            <p class="mt-2 text-copy text-body">{{ $section->note }}</p>
                        </div>
                    @endif

                    @php $tool = $crossLinks->toolFor($section); @endphp

                    @if ($tool)
                        <x-encyclopedia::tool-block :tool="$tool" />
                    @endif
                </section>
            @endforeach

            @if ($article->references->isNotEmpty())
                <x-card class="mt-9" title="منابع" heading="text-h2">
                    <ol class="flex flex-col gap-2.5 ps-5">
                        @foreach ($article->references as $reference)
                            {{-- کل ردیف یک جزیره جهت‌دار است: منبع لاتین یکپارچه چپ‌به‌راست
                                 می‌ماند و شماره فهرست سر جای خودش در راست. --}}
                            <li class="text-copy text-body">
                                <bdi>{{ $reference->title }}@if ($reference->publisher) — {{ $reference->publisher }}@endif
                                    @if ($reference->version())
                                        <span class="text-muted">({{ $reference->version() }})</span>
                                    @endif
                                </bdi>
                            </li>
                        @endforeach
                    </ol>
                </x-card>
            @endif

            @if ($related->isNotEmpty())
                <section data-print="hide" aria-labelledby="related-heading" class="mt-9">
                    <h2 id="related-heading" class="mb-3.5 text-h2 text-ink">محتوای مرتبط</h2>

                    <ul class="grid list-none gap-4 ps-0 sm:grid-cols-2">
                        @foreach ($related as $item)
                            <li>
                                <a href="{{ route('encyclopedia.show', $item->slug) }}"
                                   class="block h-full rounded-xl border border-line bg-surface px-6 py-5
                                          no-underline hover:border-primary hover:no-underline">
                                    <span class="text-note font-bold text-caution">{{ $item->type->label() }}</span>
                                    <span class="mt-1.5 block text-h4 text-ink">{{ $item->title }}</span>
                                    <span class="mt-1.5 block text-note text-muted">{{ $item->summary }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <x-disclaimer class="mt-8">
                این مطلب خلاصه و بومی‌سازی‌شده منابع بالاست و جایگزین متن اصلی استاندارد نیست.
                محتوای آن ادعای انطباق قانونی قطعی یا تشخیص پزشکی ندارد.
            </x-disclaimer>
        </article>

    </div>

</x-layouts.public>
