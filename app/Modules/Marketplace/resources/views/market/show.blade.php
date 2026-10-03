@php use App\Support\JalaliDate; @endphp

<x-layouts.public :seo="$seo" active="project-market">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['بازار پروژه', route('market.index')], [$project->title, null]]" />
    </x-slot:breadcrumb>

    <article>
        <header>
            @error('bid')
                <x-alert tone="error" class="mb-6">{{ $message }}</x-alert>
            @enderror
            @if ($project->is_private)
                <x-alert tone="info" class="mb-6" title="پروژه خصوصی">
                    این پروژه در فهرست بازار و نقشه سایت نمی‌آید و فقط کسانی که دعوتشان کرده‌اید آن را می‌بینند.
                </x-alert>
            @endif
            @unless ($open)
                <x-alert tone="caution" class="mb-6" :title="$project->status->isPublished() ? 'این پروژه دیگر پیشنهاد نمی‌پذیرد' : 'این پروژه هنوز منتشر نشده'">
                    {{ $project->status->isPublished() ? 'مهلت پیشنهاد گذشته یا کارفرما مجری‌اش را انتخاب کرده است. پروژه‌های باز را در فهرست بازار ببینید.' : 'این پیش‌نمایش فقط برای شما و مدیر است.' }}
                </x-alert>
            @endunless
            <h1 class="text-h1 text-ink">{{ $project->title }}</h1>
            <p class="mt-2 text-lede text-body">{{ $catalog->clientLabel($project) }}</p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-badge>{{ $serviceName }}</x-badge>
                <x-badge icon="compass">{{ $catalog->place($project) }}</x-badge>
                <x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge>
            </div>
        </header>

        <div class="mt-10 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0">
                <section aria-labelledby="description-heading">
                    <h2 id="description-heading" class="text-h2 text-ink">شرح کار</h2>
                    @foreach ($project->descriptionParagraphs() as $paragraph)
                        <p class="mt-4 whitespace-pre-line text-copy text-body">{{ $paragraph }}</p>
                    @endforeach
                </section>

                <section aria-labelledby="how-heading" class="mt-10">
                    <h2 id="how-heading" class="text-h3 text-ink">کار چطور پیش می‌رود</h2>
                    <ol class="mt-4 flex list-decimal flex-col gap-2 ps-6 text-copy text-body">
                        <li>مشاوران و آزمایشگاه‌های تأییدشده پیشنهادشان را با مبلغ و مرحله‌ها می‌فرستند.</li>
                        <li>کارفرما یکی را می‌پذیرد و پول هر مرحله را پیش از شروع آن در امانت فرابهداشت می‌گذارد.</li>
                        <li>با تحویل و تأیید هر مرحله، پول همان مرحله آزاد می‌شود.</li>
                    </ol>
                </section>
            </div>

            <aside class="flex flex-col gap-5">
                <x-card title="خلاصه" heading="text-h4">
                    <x-art name="market-show" class="mb-4 h-24 w-auto" />
                    <dl class="flex flex-col gap-3 text-copy">
                        <div><dt class="text-note text-muted">بودجه</dt><dd class="text-ink">{{ $catalog->budgetLabel($project) }}</dd></div>
                        @if ($project->wanted_by)
                            <div><dt class="text-note text-muted">مهلت دلخواه تحویل</dt><dd class="text-ink">{{ JalaliDate::long($project->wanted_by) }}</dd></div>
                        @endif
                        @if ($open && $project->bids_close_at)
                            <div><dt class="text-note text-muted">مهلت پیشنهاد</dt><dd class="text-ink">{{ JalaliDate::long($project->bids_close_at) }}</dd></div>
                        @endif
                        @if ($project->files->isNotEmpty())
                            <div>
                                <dt class="text-note text-muted">پیوست خصوصی</dt>
                                <dd class="text-ink">@fa($project->files->count()) فایل؛ پس از قرارداد برای مجری باز می‌شود.</dd>
                            </div>
                        @endif
                    </dl>
                    @if ($myBid && Route::has('market.bids.show'))
                        <x-button :href="route('market.bids.show', $myBid->uuid)" variant="secondary" block class="mt-5">پیشنهاد و گفت‌وگوی من</x-button>
                    @elseif ($open && ! $owner)
                        <p class="mt-5 text-note text-muted">فقط مشاوران و آزمایشگاه‌های تأییدشده فرابهداشت پیشنهاد می‌دهند؛ دادن پیشنهاد رایگان است.</p>
                        @if (Route::has('market.bid.create'))
                            <x-button :href="route('market.bid.create', $project->id)" variant="primary" block class="mt-3">ثبت پیشنهاد</x-button>
                        @endif
                    @endif
                    @if ($owner && Route::has('market.client.show'))
                        <x-button :href="route('market.client.show', $project->uuid)" variant="secondary" block class="mt-5">پیشنهادهای این پروژه</x-button>
                    @endif
                </x-card>
            </aside>
        </div>
    </article>

    <x-disclaimer class="mt-10">
        متن پروژه را کارفرما نوشته و مدیر فرابهداشت پیش از انتشار بازبینی‌اش کرده است. فرابهداشت کیفیت کار یا انطباق قانونی نتیجه را تضمین نمی‌کند؛
        پول هر مرحله تا تأیید تحویل در امانت می‌ماند.
    </x-disclaimer>

</x-layouts.public>
