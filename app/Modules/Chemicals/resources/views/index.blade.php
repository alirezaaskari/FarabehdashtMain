@php
    use App\Support\PersianNumber;
@endphp

<x-layouts.public title="بانک مواد شیمیایی"
                  description="جست‌وجو بر اساس نام فارسی، نام انگلیسی، مترادف یا شماره CAS. هر ماده یک صفحه پایدار و قابل استناد دارد."
                  :canonical="route('chemicals.index')"
                  active="chemicals">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['بانک مواد شیمیایی', null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="chemicals-index" title="بانک مواد شیمیایی"
                   lede="جست‌وجو بر اساس نام فارسی، نام انگلیسی، مترادف یا شماره CAS. هر ماده یک
                         صفحه پایدار و قابل استناد دارد.">
        <x-slot:actions>
            <x-button :href="route('chemicals.compare')" variant="secondary" icon="compass">
                مقایسه مواد
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-page-help topic="chemicals" class="mt-5" />

    <x-card size="lg" class="mt-8">
        <form method="GET" action="{{ route('chemicals.index') }}">
            <label for="cs" class="mb-2 block text-label font-semibold text-ink">جست‌وجو</label>
            <div class="flex gap-2.5">
                <input id="cs" type="search" name="q" value="{{ $query }}"
                       placeholder="مثلاً: تولوئن، Toluene یا 108-88-3"
                       class="h-field min-w-0 grow rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                <x-button type="submit" variant="primary" class="shrink-0">جست‌وجو</x-button>
            </div>
            @if ($group)
                <input type="hidden" name="group" value="{{ $group->slug }}">
            @endif
        </form>

        {{-- گروه‌ها را مدیر در «دسته‌بندی‌ها» می‌سازد؛ تا نسازد، این ردیف نیست. --}}
        @if ($groups !== [])
            <nav aria-label="گروه ماده" class="mt-4 flex flex-wrap gap-2">
                @foreach ([null, ...$groups] as $item)
                    @php $active = $item?->slug === $group?->slug; @endphp
                    <a href="{{ route('chemicals.index', array_filter(['q' => $query ?: null, 'group' => $item?->slug])) }}"
                       @if ($active) aria-current="true" @endif
                       class="inline-flex min-h-touch items-center rounded-md border px-3.5 text-label font-semibold no-underline hover:no-underline
                              {{ $active ? 'border-primary bg-primary-soft text-on-primary-soft' : 'border-line bg-surface text-muted hover:text-ink' }}">
                        {{ $item?->name ?? 'همه' }}
                    </a>
                @endforeach
            </nav>
        @endif
    </x-card>

    <div class="mt-6">
        @if ($substances->isEmpty())
            <x-empty-state art="empty-chemicals" icon="chemical"
                           title="ماده‌ای پیدا نشد"
                           :description="$query !== '' || $group
                               ? 'عبارت دیگری امتحان کنید یا بخشی از نام یا شماره CAS را وارد کنید.'
                               : 'هنوز ماده‌ای در بانک منتشر نشده است.'">
                @if ($query !== '' || $group)
                    <x-slot:action>
                        <x-button :href="route('chemicals.index')" variant="primary" size="sm">
                            پاک‌کردن جست‌وجو
                        </x-button>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @else
            <x-data-table :headers="['نام فارسی', 'نام انگلیسی', 'CAS', 'فرمول', 'حد TWA', 'مسیر مواجهه']"
                          caption="فهرست مواد شیمیایی منتشرشده">
                @foreach ($substances as $substance)
                    @php $twa = $substance->headlineLimit(); @endphp
                    <tr>
                        <td>
                            <a href="{{ route('chemicals.show', $substance->slug) }}"
                               class="inline-flex min-h-touch items-center font-semibold">
                                {{ $substance->name_fa }}
                            </a>
                        </td>
                        <td dir="ltr" data-numeric>{{ $substance->name_en }}</td>
                        <td dir="ltr" data-numeric>{{ $substance->cas_number }}</td>
                        <td dir="ltr" data-numeric>{{ $substance->formula ?? '—' }}</td>
                        <td dir="ltr" data-numeric>
                            @if ($twa)
                                {{ $twa->formattedValue() }} {{ $twa->unit }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($substance->factsOf(\App\Modules\Chemicals\Domain\Enums\FactKind::Route)->isNotEmpty())
                                {{ $substance->factsOf(\App\Modules\Chemicals\Domain\Enums\FactKind::Route)->take(2)->pluck('text')->implode(' · ') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach

                <x-slot:footnote>
                    هر مقدار حد مواجهه به یک رکورد منبع نسخه‌دار متصل است و بدون منبع ثبت نمی‌شود.
                </x-slot:footnote>
            </x-data-table>
        @endif
    </div>

    <x-disclaimer class="mt-8">
        این بانک مرجع آموزشی است و جایگزین برگه اطلاعات ایمنی (SDS) سازنده یا قضاوت کارشناسی نیست.
    </x-disclaimer>

</x-layouts.public>
