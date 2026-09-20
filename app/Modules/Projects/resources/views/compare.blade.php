@php use App\Support\Measurement\MeasurementNumber; @endphp

<x-layouts.workspace :title="'مقایسه دو دور — '.$project->title"
                     heading="مقایسه دو دور اندازه‌گیری"
                     :lede="$project->title"
                     active="tools"
                     nav="projects">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['پروژه‌های اندازه‌گیری', route('projects.index')],
            [$project->title, route('projects.show', $project->uuid)],
            ['مقایسه دو دور', null],
        ]" />
    </x-slot:breadcrumb>

    <x-slot:actions>
        <form method="GET" class="flex flex-wrap items-end gap-3" data-print="hide">
            @foreach ([['before', 'دور مبنا', $before], ['after', 'دور مقایسه', $after]] as [$name, $label, $selected])
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-xs font-bold text-ink">{{ $label }}</span>
                    <select name="{{ $name }}"
                            class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-sm text-ink">
                        @foreach ($rounds as $round)
                            <option value="{{ $round->id }}" @selected($selected?->id === $round->id)>
                                {{ $round->title }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endforeach

            <x-button type="submit" variant="secondary">نمایش مقایسه</x-button>
        </form>
    </x-slot:actions>

    @if ($error !== null)
        <x-alert tone="error" title="مقایسه ممکن نشد">{{ $error }}</x-alert>
    @elseif ($comparison === null)
        <x-empty-state title="دو دور متفاوت انتخاب کنید"
                       description="مقایسه به دو دور نیاز دارد؛ یک دور با خودش مقایسه نمی‌شود." />
    @else
        @if ($comparison->hasGaps())
            <x-alert tone="caution" class="mb-6" title="بعضی ایستگاه‌ها ناقص‌اند">
                ایستگاهی که در یکی از دو دور قرائت ندارد، در نمودار و ستون تغییر نمی‌آید.
                برای مقایسه کامل، قرائت هر دو دور را ثبت کنید.
            </x-alert>
        @endif

        {{--
            نمودار ستون کامل می‌گیرد، نه نیمی از عرض: هر ایستگاه یک جفت میله
            دارد و در ستون باریک، ایستگاه‌های سمت چپ از دید بیرون می‌افتند.
        --}}
        <x-card class="min-w-0" title="تراز هر ایستگاه، پیش و پس از اقدام">
            <x-projects::comparison-chart :chart="$chart" :comparison="$comparison" />
        </x-card>

        <div class="mt-6 grid items-start gap-4 lg:grid-cols-3">
            <x-stat label="میانگین تغییر"
                    :value="$comparison->averageChange() === null ? '—' : MeasurementNumber::format($comparison->averageChange())"
                    :unit="$comparison->unit" />
            <x-stat label="میانگین درصد تغییر"
                    :value="$comparison->averagePercentChange() === null ? '—' : MeasurementNumber::format($comparison->averagePercentChange(), 1)"
                    unit="٪" />

            <x-disclaimer>
                این جدول و نمودار فقط تغییر عددی را نشان می‌دهند. تفسیر کفایت اقدام کنترلی و
                تصمیم درباره ادامه پایش بر عهده کارشناس بهداشت حرفه‌ای است.
            </x-disclaimer>
        </div>

        <section class="mt-6" aria-labelledby="table-heading">
            <h2 id="table-heading" class="mb-3.5 text-xl font-extrabold text-ink">جدول عددی</h2>

            <x-data-table :headers="[
                              'ایستگاه',
                              $comparison->before->title,
                              $comparison->after->title,
                              'تغییر',
                              'درصد تغییر',
                          ]"
                          caption="مقایسه مقدار هر ایستگاه در دو دور، به‌همراه تغییر">
                @foreach ($comparison->rows as $row)
                    <tr>
                        <th scope="row" class="px-4 py-3.5 text-start text-sm font-semibold text-ink">
                            {{ $row->station }}
                        </th>
                        <td dir="ltr" data-numeric>
                            {{ $row->before === null ? '—' : MeasurementNumber::format($row->before) }}
                        </td>
                        <td dir="ltr" data-numeric>
                            {{ $row->after === null ? '—' : MeasurementNumber::format($row->after) }}
                        </td>
                        <td class="font-bold text-ink" dir="ltr" data-numeric>
                            {{ $row->change() === null ? '—' : MeasurementNumber::format($row->change()) }}
                        </td>
                        <td dir="ltr" data-numeric>
                            {{ $row->percentChange() === null ? '—' : MeasurementNumber::format($row->percentChange(), 1).'٪' }}
                        </td>
                    </tr>
                @endforeach

                <x-slot:footnote>
                    خط تیره یعنی آن ایستگاه در آن دور قرائت ندارد؛ برای چنین ردیفی تغییر محاسبه نمی‌شود.
                </x-slot:footnote>
            </x-data-table>
        </section>
    @endif

</x-layouts.workspace>
