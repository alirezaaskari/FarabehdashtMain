<x-layouts.public title="جست‌وجو"
                  description="جست‌وجو در دانشنامه، بانک مواد شیمیایی، ابزارها، دوره‌ها و فروشگاه فرابهداشت."
                  noindex>

    <x-page-header title="جست‌وجو"
                   lede="در دانشنامه، مواد شیمیایی، ابزارها، دوره‌ها و فروشگاه. شماره CAS مستقیم به صفحه ماده می‌رود." />

    <x-card size="lg" class="mt-8">
        {{-- روی گوشی همین صفحه جست‌وجوی تمام‌صفحه است؛ پیشنهاد فوری زیر کادر باز می‌شود. --}}
        <form method="GET" action="{{ route('workspace.search') }}" role="search"
              data-search-suggest="{{ route('workspace.search.suggest') }}">
            <label for="site-search" class="mb-2 block text-label font-semibold text-ink">عبارت جست‌وجو</label>
            <div class="flex gap-2.5">
                <input id="site-search" type="search" name="q" value="{{ $query->raw }}"
                       placeholder="مثلاً: صدا، بنزن یا 71-43-2"
                       class="h-field min-w-0 grow rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                <x-button type="submit" variant="primary" icon="search" class="shrink-0">جست‌وجو</x-button>
            </div>
            <div data-search-panel hidden class="mt-2 rounded-lg border border-line-strong bg-surface py-1"></div>
        </form>
    </x-card>

    <div class="mt-8">
        @if ($query->raw === '')
            <p class="text-copy text-muted">عبارتی بنویسید تا در همه بخش‌های فرابهداشت جست‌وجو شود.</p>
        @elseif (! $query->isSearchable())
            <x-alert tone="info">دست‌کم @fa(\App\Support\Search\SearchQuery::MIN_LENGTH) حرف بنویسید.</x-alert>
        @elseif ($groups === [])
            <x-empty-state icon="search"
                           title="نتیجه‌ای پیدا نشد"
                           description="املای دیگری امتحان کنید، بخشی از کلمه را بنویسید یا نام انگلیسی را جست‌وجو کنید." />
        @else
            <p class="mb-6 text-label text-muted" role="status">@fa($total) نتیجه برای «{{ $query->raw }}»</p>

            <div class="flex flex-col gap-6">
                @foreach ($groups as $group)
                    <x-card :title="$group->title" :subtitle="\App\Support\PersianDigits::from($group->total).' نتیجه'">
                        <ul class="flex flex-col divide-y divide-line-soft">
                            @foreach ($group->hits as $hit)
                                <li class="py-3">
                                    <a href="{{ $hit->url }}" class="inline-flex min-h-touch items-center gap-2 text-label font-semibold">
                                        {{ $hit->title }}
                                        @if ($hit->code)
                                            <span dir="ltr" data-numeric class="text-note font-semibold text-muted">{{ $hit->code }}</span>
                                        @endif
                                    </a>
                                    @if ($hit->summary !== '')
                                        <p class="text-note text-muted">{{ \Illuminate\Support\Str::limit($hit->summary, 180) }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        @if ($group->moreUrl && $group->total > count($group->hits))
                            <div class="mt-4">
                                <x-button :href="$group->moreUrl" variant="secondary" size="sm">همه نتایج این بخش</x-button>
                            </div>
                        @endif
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>

</x-layouts.public>
