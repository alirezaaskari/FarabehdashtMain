@php use App\Support\Measurement\MeasurementNumber; @endphp

<x-layouts.workspace :title="'مقایسه دو دور — '.$project->title" heading="مقایسه دو دور اندازه‌گیری">

    <div class="max-w-5xl">

        <nav aria-label="مسیر صفحه" class="mb-4 text-sm" data-print="hide">
            <a href="{{ route('projects.index') }}"
               class="inline-flex h-touch items-center text-primary">پروژه‌ها</a>
            <span class="text-muted"> / </span>
            <a href="{{ route('projects.show', $project->uuid) }}"
               class="inline-flex h-touch items-center text-primary">{{ $project->title }}</a>
        </nav>


        <form method="GET" class="mt-4 flex flex-wrap items-end gap-3" data-print="hide">
            @foreach ([['before', 'دور مبنا', $before], ['after', 'دور مقایسه', $after]] as [$name, $label, $selected])
                <label class="flex flex-col gap-1.5">
                    <span class="text-xs font-bold text-ink">{{ $label }}</span>
                    <select name="{{ $name }}"
                            class="h-field rounded-md border border-line-strong bg-surface px-3 text-sm text-ink">
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

        @if ($error !== null)
            <x-alert tone="error" class="mt-6" title="مقایسه ممکن نشد">{{ $error }}</x-alert>
        @elseif ($comparison === null)
            <x-empty-state class="mt-8"
                           title="دو دور متفاوت انتخاب کنید"
                           description="مقایسه به دو دور نیاز دارد؛ یک دور با خودش مقایسه نمی‌شود." />
        @else
            @if ($comparison->hasGaps())
                <x-alert tone="caution" class="mt-6" title="بعضی ایستگاه‌ها ناقص‌اند">
                    ایستگاهی که در یکی از دو دور قرائت ندارد، در نمودار و ستون تغییر نمی‌آید.
                    برای مقایسه کامل، قرائت هر دو دور را ثبت کنید.
                </x-alert>
            @endif

            <div class="mt-8">
                <x-projects::comparison-chart :chart="$chart" :comparison="$comparison" />
            </div>

            <section class="mt-8" aria-labelledby="table-heading">
                <h2 id="table-heading" class="text-lg font-extrabold text-ink">جدول عددی</h2>

                <div class="mt-3 overflow-x-auto rounded-xl border border-line">
                    <table class="w-full text-sm">
                        <caption class="sr-only">مقایسه مقدار هر ایستگاه در دو دور، به‌همراه تغییر</caption>
                        <thead class="bg-surface-2">
                            <tr>
                                <th scope="col" class="p-3 text-start font-bold text-ink">ایستگاه</th>
                                <th scope="col" class="p-3 text-start font-bold text-ink">{{ $comparison->before->title }}</th>
                                <th scope="col" class="p-3 text-start font-bold text-ink">{{ $comparison->after->title }}</th>
                                <th scope="col" class="p-3 text-start font-bold text-ink">تغییر</th>
                                <th scope="col" class="p-3 text-start font-bold text-ink">درصد تغییر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comparison->rows as $row)
                                <tr class="border-t border-line">
                                    <th scope="row" class="p-3 text-start font-semibold text-ink">{{ $row->station }}</th>
                                    <td class="p-3 text-muted" dir="ltr" data-numeric>
                                        {{ $row->before === null ? '—' : MeasurementNumber::format($row->before) }}
                                    </td>
                                    <td class="p-3 text-muted" dir="ltr" data-numeric>
                                        {{ $row->after === null ? '—' : MeasurementNumber::format($row->after) }}
                                    </td>
                                    <td class="p-3 font-bold text-ink" dir="ltr" data-numeric>
                                        {{ $row->change() === null ? '—' : MeasurementNumber::format($row->change()) }}
                                    </td>
                                    <td class="p-3 text-muted" dir="ltr" data-numeric>
                                        {{ $row->percentChange() === null ? '—' : MeasurementNumber::format($row->percentChange(), 1).'٪' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <x-stat label="میانگین تغییر"
                        :value="$comparison->averageChange() === null ? '—' : MeasurementNumber::format($comparison->averageChange())"
                        :unit="$comparison->unit" />
                <x-stat label="میانگین درصد تغییر"
                        :value="$comparison->averagePercentChange() === null ? '—' : MeasurementNumber::format($comparison->averagePercentChange(), 1)"
                        unit="٪" />
            </div>

            <x-disclaimer class="mt-8" size="md">
                این جدول و نمودار فقط تغییر عددی را نشان می‌دهند. تفسیر کفایت اقدام کنترلی و
                تصمیم درباره ادامه پایش بر عهده کارشناس بهداشت حرفه‌ای است.
            </x-disclaimer>
        @endif

    </div>

</x-layouts.workspace>
