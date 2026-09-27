<x-layouts.workspace art="consulting-reviews-pick" title="بررسی گزارش توسط متخصص"
                     heading="بررسی گزارش توسط متخصص"
                     lede="یک مشاور تأییدشده گزارش صادرشده شما را می‌خواند و روی هر بخش یادداشت و یک جمع‌بندی می‌نویسد."
                     nav="consulting-orders" help="consulting-reviews">

    @if (Route::has('reports.index'))
        <x-slot:breadcrumb>
            <x-breadcrumb :items="[['گزارش‌ها', route('reports.index')], ['بررسی متخصص', null]]" />
        </x-slot:breadcrumb>
    @endif

    @if ($report)
        <div class="mb-6">
            <x-alert tone="info">گزارش انتخاب‌شده: {{ $report->title }} · <span dir="ltr" data-numeric>{{ $report->trackingCode }}</span></x-alert>
        </div>
    @endif

    @if (! $open)
        <x-alert tone="info">بررسی گزارش فعلاً فروخته نمی‌شود. کمی بعد دوباره سر بزنید.</x-alert>
    @elseif ($services->isEmpty())
        <x-empty-state art="empty-consulting-reviews" icon="user" title="هنوز بررسی‌کننده‌ای نیست"
                       description="وقتی مشاوری خدمت بررسی گزارش تعریف کند و مدیر آن را تأیید کند، این‌جا با قیمتش می‌آید.">
            <x-slot:action>
                <x-button :href="route('consulting.index')" variant="primary">فهرست مشاوران</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <ul class="flex list-none flex-col gap-4 ps-0">
            @foreach ($services as $service)
                @php
                    $profile = $service->profile;
                    $photo = $profile->photo_id ? ($photos[$profile->photo_id] ?? null) : null;
                @endphp
                <li class="rounded-xl border border-line bg-surface px-5 py-5">
                    <div class="flex flex-wrap items-start gap-4">
                        @if ($photo)
                            <img src="{{ $photo->url }}" alt="" width="56" height="56" loading="lazy" class="size-14 shrink-0 rounded-full object-cover">
                        @else
                            <span aria-hidden="true" class="flex size-14 shrink-0 items-center justify-center rounded-full bg-primary-soft text-h4 text-on-primary-soft">{{ mb_substr((string) $profile->display_name, 0, 1) }}</span>
                        @endif
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <h2 class="text-h4 text-ink">
                                <a href="{{ route('consulting.show', $profile->slug) }}" class="inline-flex min-h-touch items-center">{{ $profile->display_name }}</a>
                            </h2>
                            <p class="text-copy text-body">{{ $profile->headline }}</p>
                            <p class="text-note text-muted">{{ $service->title }} · تحویل تا @fa($dueDays) روز پس از پذیرش</p>
                        </div>
                        <p class="text-h4 text-ink">{{ $service->price()->format() }}</p>
                    </div>
                    @foreach ($service->paragraphs() as $paragraph)
                        <p class="mt-3 text-copy text-body">{{ $paragraph }}</p>
                    @endforeach
                    <div class="mt-4">
                        <x-button :href="route('consulting.orders.create', ['uuid' => $service->uuid, 'report' => $report?->uuid])" variant="primary">انتخاب این متخصص</x-button>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="mt-4 text-note text-muted">
            مبلغ تا پایان کار نزد فرابهداشت امانت می‌ماند. نظر متخصص کارشناسی است؛ تأیید رسمی گزارش یا انطباق قانونی نیست و گزارش مهر «تأییدشده» نمی‌گیرد.
        </p>
    @endif

</x-layouts.workspace>
