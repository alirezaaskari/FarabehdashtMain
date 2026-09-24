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
            <span class="flex items-center gap-2 font-bold">
                <x-icon name="check" :size="17" :stroke="2.2" />
                داده‌های این صفحه بررسی‌شده است
            </span>
            <span>آخرین بررسی: <strong>{{ JalaliDate::short($substance->reviewed_at) }}</strong></span>
        </div>
    @endif

    <div class="mt-6 grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:items-start">
        <div>
            <h1 class="text-display text-ink">{{ $substance->name_fa }}</h1>
            <p class="mt-1.5 text-lede text-muted" dir="ltr" data-numeric>{{ $substance->name_en }}</p>

            @if ($substance->description)
                <p class="mt-4 max-w-[46rem] text-copy text-body">{{ $substance->description }}</p>
            @endif

            @if ($substance->synonyms->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($substance->synonyms as $synonym)
                        <span class="inline-flex h-8 items-center rounded-full border border-line bg-surface px-3.5 text-note font-semibold text-muted">
                            مترادف: {{ $synonym->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        <dl class="grid grid-cols-2 gap-4 rounded-xl border border-line bg-surface px-6 py-5.5">
            <div>
                <dt class="text-note text-muted">شماره CAS</dt>
                <dd class="mt-1 text-label font-bold text-ink" dir="ltr" data-numeric>{{ $substance->cas_number }}</dd>
            </div>
            <div>
                <dt class="text-note text-muted">فرمول شیمیایی</dt>
                <dd class="mt-1 text-label font-bold text-ink" dir="ltr" data-numeric>{{ $substance->formula ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-note text-muted">جرم مولکولی</dt>
                <dd class="mt-1 text-label font-bold text-ink" dir="ltr" data-numeric>
                    @if ($substance->molar_mass)
                        {{ MeasurementNumber::format($substance->molar_mass, 2) }} g/mol
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-note text-muted">حالت فیزیکی</dt>
                <dd class="mt-1 text-label font-bold text-ink">{{ $substance->physical_state ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    <x-card size="lg" class="mt-8" title="حدود مواجهه شغلی" heading="text-h2">
        @if ($limits === [])
            <x-empty-state icon="chemical" title="حد مواجهه‌ای ثبت نشده است" />
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
    </x-card>

    <div class="mt-6 grid gap-6 md:grid-cols-3">
        @foreach ([
            ['route', 'مسیرهای مواجهه', $routes],
            ['symptom', 'علائم و اثرات', $symptoms],
            ['protection', 'حفاظت فردی', $protections],
        ] as [$icon, $title, $facts])
            <x-card :title="$title">
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
            </x-card>
        @endforeach
    </div>

    @if ($substance->sampling_media || $substance->analysis_method)
        <x-card size="lg" class="mt-6" title="روش نمونه‌برداری و تحلیل">
            <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ([
                    ['رسانه نمونه‌برداری', $substance->sampling_media],
                    ['دبی پیشنهادی', $substance->sampling_flow],
                    ['روش تحلیل', $substance->analysis_method],
                    ['شماره روش مرجع', $substance->method_number],
                ] as [$label, $value])
                    <div>
                        <dt class="text-note text-muted">{{ $label }}</dt>
                        <dd class="mt-1 text-label font-bold text-ink">{{ $value ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>
    @endif

    @if ($tools !== [])
        <x-card size="lg" class="mt-6" title="محاسبه با این ماده">
            <p class="text-copy text-muted">
                جرم مولکولی و مشخصات این ماده در ابزارهای زیر قابل استفاده است.
            </p>
            <div class="mt-4 flex flex-wrap gap-2.5">
                @foreach ($tools as $tool)
                    <x-button :href="route('tools.show', $tool->slug)" variant="secondary">
                        {{ $tool->title }}
                    </x-button>
                @endforeach
            </div>
        </x-card>
    @endif

    <x-mentioned-in :items="$mentionedIn" class="mt-8" />

    <x-disclaimer class="mt-8">
        این صفحه مرجع آموزشی است و جایگزین برگه اطلاعات ایمنی (SDS) سازنده، قضاوت کارشناسی یا
        ارزیابی پزشکی نیست. پیش از هر تصمیم عملیاتی به SDS معتبر ماده و آخرین ویرایش حدود مجاز
        مواجهه استناد کنید.
    </x-disclaimer>

</x-layouts.public>
