@php
    use App\Support\JalaliDate;
    use App\Support\Measurement\MeasurementNumber;
    use App\Modules\Chemicals\Domain\Enums\LimitType;
@endphp

<x-layouts.public :seo="$seo" active="chemicals">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['خانه', route('home')],
            ['بانک مواد شیمیایی', route('chemicals.index')],
            [$substance->name_fa, null],
        ]" />
    </x-slot:breadcrumb>

    @if ($substance->reviewed_at)
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-note border border-primary-line bg-primary-soft
                    px-6 py-3.5 text-note text-on-primary-soft">
            <span class="flex items-center gap-2 font-semibold">
                <x-icon name="check" :size="17" :stroke="2.2" />
                داده‌های این صفحه بررسی‌شده است
            </span>
            <span>آخرین بررسی: <strong>{{ JalaliDate::short($substance->reviewed_at) }}</strong></span>
        </div>
    @endif

    <div class="mt-6 grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:items-start">
        <div>
            <x-art name="chemicals-show" class="mb-4 h-24 w-auto lg:h-28" />
            <h1 class="text-display text-ink">{{ $substance->name_fa }}</h1>
            {{-- bdi و نه dir روی کل بند: نام لاتین باید زیر عنوان راست‌چین بماند. --}}
            <p class="mt-1.5 text-lede text-muted"><bdi dir="ltr" data-numeric>{{ $substance->name_en }}</bdi></p>

            @if ($substance->description)
                <p class="mt-4 max-w-[46rem] text-copy text-body">{{ $substance->description }}</p>
            @endif

            @if ($substance->synonyms->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($substance->synonyms as $synonym)
                        <span class="inline-flex h-8 items-center rounded-md border border-line bg-surface px-3.5 text-note font-semibold text-muted">
                            مترادف: {{ $synonym->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        <dl class="grid grid-cols-2 gap-4 rounded-xl border border-line bg-surface px-6 py-5.5">
            <div>
                <dt class="text-note text-muted">شماره CAS</dt>
                <dd class="mt-1 text-label font-semibold text-ink" dir="ltr" data-numeric>{{ $substance->cas_number }}</dd>
            </div>
            <div>
                <dt class="text-note text-muted">فرمول شیمیایی</dt>
                <dd class="mt-1 text-label font-semibold text-ink" dir="ltr" data-numeric>{{ $substance->formula ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-note text-muted">جرم مولکولی</dt>
                <dd class="mt-1 text-label font-semibold text-ink" dir="ltr" data-numeric>
                    @if ($substance->molar_mass)
                        {{ MeasurementNumber::format($substance->molar_mass, 2) }} g/mol
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-note text-muted">حالت فیزیکی</dt>
                <dd class="mt-1 text-label font-semibold text-ink">{{ $substance->physical_state ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    <section aria-labelledby="limits-heading" class="mt-12">
        <h2 id="limits-heading" class="mb-4 text-h2 text-ink">حدود مواجهه شغلی</h2>
        @if ($limits === [])
            <x-empty-state art="empty-chem-limits" icon="chemical" title="حد مواجهه‌ای ثبت نشده است"
                           description="برای این ماده هنوز حدی از مراجع ثبت نشده است. پیش از هر مقایسه، حد را از متن اصلی مرجع بررسی کنید." />
        @else
            <x-data-table :headers="['مرجع', 'نوع حد', 'مقدار', 'منبع و سال']"
                          caption="حدود مواجهه شغلی چندمرجعی این ماده">
                @foreach ($limits as $limit)
                    <tr>
                        <td class="font-semibold text-ink"
                            @if ($limit->authority->usesLatinScript()) dir="ltr" data-numeric @endif
                        >{{ $limit->authority->label() }}</td>
                        <td>
                            {{ $limit->type->shortLabel() }}
                            @unless ($limit->type->comparableWithMeasurement())
                                <x-badge tone="caution" class="ms-1.5">غیرقابل مقایسه با اندازه‌گیری معمول</x-badge>
                            @endunless
                        </td>
                        <td dir="ltr" data-numeric>{{ $limit->formattedValue() }} {{ $limit->unit }}</td>
                        <td>
                            @if ($limit->referenceLine())
                                <span @if ($limit->authority->usesLatinScript()) dir="ltr" data-numeric @endif>
                                    {{ $limit->referenceLine() }}
                                </span>
                            @else
                                <span class="text-danger">بدون منبع</span>
                            @endif
                        </td>
                    </tr>
                @endforeach

                <x-slot:footnote>
                    هر مقدار حد مواجهه به یک رکورد منبع نسخه‌دار متصل است و بدون منبع منتشر نمی‌شود.
                </x-slot:footnote>
            </x-data-table>
        @endif
    </section>

    <div class="mt-12 grid gap-8 md:grid-cols-3">
        @foreach ([
            ['route', 'مسیرهای مواجهه', $routes],
            ['symptom', 'علائم و اثرات', $symptoms],
            ['protection', 'حفاظت فردی', $protections],
        ] as [$icon, $title, $facts])
            <section class="border-t border-line-strong pt-5">
                <h2 class="mb-4 text-h3 text-ink">{{ $title }}</h2>
                @if ($facts->isEmpty())
                    <p class="text-note text-muted">ثبت نشده است.</p>
                @else
                    <ul class="flex list-none flex-col gap-2.5 ps-0">
                        @foreach ($facts as $fact)
                            <li class="flex items-start gap-2 text-copy text-body">
                                <x-icon :name="$icon === 'route' ? 'wind' : ($icon === 'symptom' ? 'pulse' : 'shield')"
                                        :size="16" class="mt-1 shrink-0 text-muted" />
                                {{ $fact->text }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>

    @if ($substance->sampling_media || $substance->analysis_method)
        <section aria-labelledby="sampling-heading" class="mt-12 border-t border-line-strong pt-5">
            <h2 id="sampling-heading" class="text-h3 text-ink">روش نمونه‌برداری و تحلیل</h2>
            <dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                {{-- خانه ثبت‌نشده پنهان است؛ ردیفی از «—» چیزی به کاربر نمی‌گوید. --}}
                @foreach (array_filter([
                    ['رسانه نمونه‌برداری', $substance->sampling_media],
                    ['دبی پیشنهادی', $substance->sampling_flow],
                    ['روش تحلیل', $substance->analysis_method],
                    ['شماره روش مرجع', $substance->method_number],
                ], static fn (array $row): bool => filled($row[1])) as [$label, $value])
                    <div>
                        <dt class="text-note text-muted">{{ $label }}</dt>
                        <dd class="mt-1 text-label font-semibold text-ink">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- پل بانک مواد به ابزارها. جرم مولکولی همین ماده در
         فرم ابزار پر می‌شود تا کاربر عدد را از این صفحه رونویسی نکند. --}}
    @if ($tools !== [])
        <section aria-labelledby="calculate-with" class="mt-12 rounded-xl border border-line bg-surface-2 px-6 py-7 md:px-8">
            <h2 id="calculate-with" class="text-h3 text-ink">محاسبه با این ماده</h2>
            <p class="mt-2 text-copy text-muted">
                @if ($substance->molar_mass)
                    جرم مولکولی {{ $substance->name_fa }} در فرم این ابزارها از پیش وارد شده است.
                @else
                    این ماده در ابزارهای زیر به کار می‌آید.
                @endif
            </p>
            <div class="mt-5 flex flex-wrap gap-2.5">
                @foreach ($tools as $tool)
                    <x-button :href="route('tools.show', array_filter(['slug' => $tool->slug, 'molecular_weight' => $substance->molar_mass]))"
                              variant="secondary" icon="calculator">
                        {{ $tool->title }}
                    </x-button>
                @endforeach
            </div>
        </section>
    @endif

    <x-mentioned-in :items="$mentionedIn" class="mt-8" />

    <x-disclaimer class="mt-8">
        این صفحه مرجع آموزشی است و جایگزین برگه اطلاعات ایمنی (SDS) سازنده، قضاوت کارشناسی یا
        ارزیابی پزشکی نیست. پیش از هر تصمیم عملیاتی به SDS معتبر ماده و آخرین ویرایش حدود مجاز
        مواجهه استناد کنید.
    </x-disclaimer>

</x-layouts.public>
