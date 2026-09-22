@php
    use App\Support\JalaliDate;
    use App\Support\PersianNumber;
@endphp

<x-layouts.public title="دانشنامه تخصصی"
                  description="مقاله، راهنما، واژه‌نامه، روش اندازه‌گیری، نمونه موردی و قوانین بهداشت حرفه‌ای — هر مورد با بازبین علمی، تاریخ بازبینی و منابع نسخه‌دار."
                  active="encyclopedia">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['دانشنامه', null]]" />
    </x-slot:breadcrumb>

    <x-page-header title="دانشنامه تخصصی"
                   lede="مقاله، راهنما، واژه‌نامه، روش اندازه‌گیری، نمونه موردی و قوانین — هر مورد با
                         نویسنده، بازبین علمی، تاریخ بازبینی و منابع نسخه‌دار." />

    <div class="mt-8 flex flex-col gap-7 lg:flex-row lg:items-start">

        {{-- فیلترها با GET کار می‌کنند: نشانی فیلترشده باید قابل اشتراک باشد. --}}
        <form method="GET" action="{{ route('encyclopedia.index') }}"
              class="w-full shrink-0 rounded-xl border border-line bg-surface p-6 lg:w-[15.625rem]">
            <h2 class="mb-4 text-copy font-extrabold text-ink">فیلترها</h2>

            <fieldset class="mb-5">
                <legend class="mb-2.5 text-note font-bold text-muted">نوع محتوا</legend>

                @foreach ($types as $type)
                    <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-sm text-body">
                        <input type="checkbox" name="type[]" value="{{ $type->value }}"
                               @checked(in_array($type->value, $selectedTypes, true))
                               class="h-[1.0625rem] w-[1.0625rem] accent-primary">
                        {{ $type->label() }}
                    </label>
                @endforeach
            </fieldset>

            <fieldset class="mb-5">
                <legend class="mb-2.5 text-note font-bold text-muted">وضعیت بازبینی</legend>

                @foreach ([['fresh', 'بازبینی‌شده و معتبر'], ['due', 'نیازمند بازبینی']] as [$value, $label])
                    <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-sm text-body">
                        <input type="radio" name="review" value="{{ $value }}"
                               @checked($review === $value)
                               class="h-[1.0625rem] w-[1.0625rem] accent-primary">
                        {{ $label }}
                    </label>
                @endforeach
            </fieldset>

            <div class="flex flex-col gap-2">
                <x-button type="submit" variant="primary" block>اعمال فیلتر</x-button>
                <x-button :href="route('encyclopedia.index')" variant="secondary" block>پاک‌کردن فیلترها</x-button>
            </div>
        </form>

        <div class="min-w-0 grow">
            <p class="mb-4 text-sm text-muted">
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
                <ul class="grid list-none gap-4 ps-0 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($articles as $article)
                        @php $level = $levels[$article->id]; @endphp
                        <li>
                            <a href="{{ route('encyclopedia.show', $article->slug) }}"
                               class="flex h-full flex-col rounded-xl border border-line bg-surface px-6 py-5.5
                                      no-underline hover:border-primary hover:no-underline">
                                <span class="text-xs font-bold text-caution">{{ $article->type->label() }}</span>

                                <span class="mt-2 block text-h4 leading-8 text-ink">{{ $article->title }}</span>

                                <span class="mt-2 block text-sm text-muted">{{ $article->summary }}</span>

                                <span class="mt-auto flex items-center justify-between gap-3 border-t border-line-soft pt-3.5">
                                    <span class="text-xs text-muted">
                                        بازبین: {{ $article->reviewer?->name ?? 'ثبت نشده' }}
                                    </span>

                                    <span @class([
                                        'text-xs',
                                        'text-danger font-bold' => $level === $staleLevel,
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
