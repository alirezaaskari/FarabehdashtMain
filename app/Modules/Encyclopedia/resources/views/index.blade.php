@php
    use App\Support\JalaliDate;
    use App\Support\PersianNumber;
@endphp

<x-layouts.public title="دانشنامه تخصصی"
                  description="مقاله، راهنما، واژه‌نامه، روش اندازه‌گیری، نمونه موردی و قوانین بهداشت حرفه‌ای — هر مورد با بازبین علمی، تاریخ بازبینی و منابع نسخه‌دار."
                  :canonical="route('encyclopedia.index')"
                  active="encyclopedia">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['دانشنامه', null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="scene.books" title="دانشنامه تخصصی"
                   lede="مقاله، راهنما، واژه‌نامه، روش اندازه‌گیری، نمونه موردی و قوانین — هر مورد با
                         نویسنده، بازبین علمی، تاریخ بازبینی و منابع نسخه‌دار." />

    <x-page-help topic="encyclopedia" class="mt-5" />

    <div class="mt-10 flex flex-col gap-7 lg:flex-row lg:items-start lg:gap-12">

        {{-- فیلترها با GET کار می‌کنند: نشانی فیلترشده باید قابل اشتراک باشد. --}}
        <form method="GET" action="{{ route('encyclopedia.index') }}"
              class="w-full shrink-0 border-y border-line py-2 lg:w-[15rem] lg:border-0 lg:py-0">
            @php $activeFilters = count($selectedTypes) + ($review ? 1 : 0); @endphp

            {{-- روی موبایل جمع است تا نتایج زیر ۶۰۰ پیکسل فیلتر گم نشوند. --}}
            <x-disclosure open-from="lg">
                <x-slot:summary>
                    <h2 class="text-copy font-bold text-ink">
                        فیلترها
                        @if ($activeFilters > 0)
                            <span class="text-label font-semibold text-primary">(@fa($activeFilters))</span>
                        @endif
                    </h2>
                </x-slot:summary>

                <div class="pt-2 pb-4 lg:pb-0">
                    <fieldset class="mb-5">
                        <legend class="mb-2.5 text-note font-semibold text-muted">نوع محتوا</legend>

                        @foreach ($types as $type)
                            <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                <input type="checkbox" name="type[]" value="{{ $type->value }}"
                                       @checked(in_array($type->value, $selectedTypes, true))
                                       class="size-5 shrink-0 accent-primary">
                                {{ $type->label() }}
                            </label>
                        @endforeach
                    </fieldset>

                    <fieldset class="mb-5">
                        <legend class="mb-2.5 text-note font-semibold text-muted">وضعیت بازبینی</legend>

                        @foreach ([['fresh', 'بازبینی‌شده و معتبر'], ['due', 'نیازمند بازبینی']] as [$value, $label])
                            <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                <input type="radio" name="review" value="{{ $value }}"
                                       @checked($review === $value)
                                       class="size-5 shrink-0 accent-primary">
                                {{ $label }}
                            </label>
                        @endforeach
                    </fieldset>

                    <div class="flex flex-col gap-2">
                        <x-button type="submit" variant="primary" block>اعمال فیلتر</x-button>
                        <x-button :href="route('encyclopedia.index')" variant="secondary" block>پاک‌کردن فیلترها</x-button>
                    </div>
                </div>
            </x-disclosure>
        </form>

        <div class="min-w-0 grow">
            <p class="border-b border-line-strong pb-3 text-label text-muted">
                {{ PersianNumber::format($articles->total()) }} مورد یافت شد
            </p>

            @if ($articles->isEmpty())
                <x-empty-state icon="book"
                               title="با این فیلترها محتوایی نیست"
                               description="فیلتر نوع محتوا یا وضعیت بازبینی را بردارید تا فهرست کامل را ببینید.">
                    <x-slot:action>
                        <x-button :href="route('encyclopedia.index')" variant="primary" size="sm">
                            دیدن همه محتواها
                        </x-button>
                    </x-slot:action>
                </x-empty-state>
            @else
                <ul class="list-none ps-0">
                    @foreach ($articles as $article)
                        @php $level = $levels[$article->id]; @endphp
                        <li class="border-b border-line">
                            <a href="{{ route('encyclopedia.show', $article->slug) }}"
                               class="group block py-5 no-underline hover:no-underline">
                                <span class="block text-h4 text-ink group-hover:text-primary">{{ $article->title }}</span>

                                <span class="mt-1.5 block max-w-[48rem] text-label text-muted">{{ $article->summary }}</span>

                                <span class="mt-2.5 flex flex-wrap items-center gap-x-2 text-note text-muted">
                                    <span class="font-semibold text-body">{{ $article->type->label() }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>بازبین: {{ $article->reviewer?->name ?? 'ثبت نشده' }}</span>
                                    <span aria-hidden="true">·</span>

                                    <span @class([
                                        'text-note',
                                        'text-danger font-semibold' => $level === $staleLevel,
                                        'text-muted' => $level !== $staleLevel,
                                    ])>
                                        {{ $article->reviewed_at ? JalaliDate::short($article->reviewed_at) : 'بدون بازبینی' }}
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-7">{{ $articles->links() }}</div>
            @endif
        </div>

    </div>

    <x-disclaimer class="mt-8">
        محتوای دانشنامه خلاصه و بومی‌سازی‌شده منابع نام‌برده است و جایگزین متن اصلی استاندارد
        نیست. هیچ مطلبی ادعای تشخیص پزشکی یا انطباق قانونی قطعی ندارد.
    </x-disclaimer>

</x-layouts.public>
