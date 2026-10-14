{{-- @var \App\Modules\Tools\Domain\AssessmentComparison|null $comparison --}}

<x-layouts.workspace :title="$comparison ? 'مقایسه ارزیابی‌ها — '.$comparison->method : 'مقایسه ارزیابی‌ها'"
                     heading="مقایسه ارزیابی‌ها"
                     :lede="$comparison?->method ?? 'ارزیابی‌های پوسچر ذخیره‌شده، کنار هم'"
                     active="tools"
                     nav="calculations" help="assessment-compare">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['محاسبات ذخیره‌شده', route('tools.calculations.index')],
            ['مقایسه ارزیابی‌ها', null],
        ]" />
    </x-slot:breadcrumb>

    @if ($comparison === null)
        <x-alert tone="error" title="مقایسه ممکن نشد">
            {{ $error }}
            ارزیابی‌ها را از صفحه یکی از ارزیابی‌های ذخیره‌شده انتخاب کنید.
        </x-alert>

        <x-button :href="route('tools.calculations.index')" variant="secondary" icon="back" class="mt-4">
            محاسبات ذخیره‌شده
        </x-button>
    @else
        @php
            $titles = array_map(fn ($c) => $c['title'].' · '.$c['date'], $comparison->columns);
            $headers = ['امتیاز', ...$titles];
            $trends = ['better' => ['primary', 'بهتر'], 'worse' => ['danger', 'بدتر'], 'same' => ['neutral', 'بی‌تغییر']];

            if ($comparison->isBeforeAfter()) {
                $headers[] = 'تغییر';
            }
        @endphp

        @if ($comparison->isBeforeAfter())
            <p class="mb-4 text-copy text-muted">
                «{{ $comparison->columns[0]['title'] }}» زودتر ثبت شده و مبنا است؛ تغییر یعنی
                «{{ $comparison->columns[1]['title'] }}» نسبت به آن. در این روش امتیاز کمتر یعنی وضعیت بهتر.
            </p>
        @endif

        <section aria-labelledby="scores-heading">
            <h2 id="scores-heading" class="mb-3.5 text-h3 font-bold text-ink">امتیازها</h2>

            <x-data-table :headers="$headers" caption="امتیازهای هر ارزیابی کنار هم">
                @foreach ($comparison->scores as $row)
                    <tr>
                        <th scope="row" class="px-4 py-3.5 text-start text-label font-semibold text-ink">{{ $row['label'] }}</th>
                        @foreach ($row['values'] as $value)
                            <td><span dir="ltr" data-numeric>{{ $value }}</span></td>
                        @endforeach
                        @if ($comparison->isBeforeAfter())
                            <td>
                                @if ($row['trend'] !== null)
                                    <span class="inline-flex items-center gap-2">
                                        <span dir="ltr" data-numeric>{{ $row['change'] }}</span>
                                        <x-badge :tone="$trends[$row['trend']][0]">{{ $trends[$row['trend']][1] }}</x-badge>
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </x-data-table>
        </section>

        <section aria-labelledby="differences-heading" class="mt-8">
            <h2 id="differences-heading" class="mb-3.5 text-h3 font-bold text-ink">پاسخ‌هایی که فرق دارد</h2>

            @if ($comparison->differences === [])
                <p class="text-copy text-muted">همه پاسخ‌ها یکسان‌اند؛ این ارزیابی‌ها وضعیت یکسانی را ثبت کرده‌اند.</p>
            @else
                <x-data-table :headers="['پاسخ', ...$titles]"
                              caption="پاسخ‌هایی که میان ارزیابی‌ها فرق دارد">
                    @foreach ($comparison->differences as $row)
                        <tr>
                            <th scope="row" class="px-4 py-3.5 text-start text-label font-semibold text-ink">{{ $row['label'] }}</th>
                            @foreach ($row['values'] as $value)
                                <td>{{ $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach

                    <x-slot:footnote>
                        فقط پاسخ‌هایی آمده‌اند که دست‌کم در یک ارزیابی فرق دارند؛ همین‌ها امتیاز را جابه‌جا کرده‌اند.
                    </x-slot:footnote>
                </x-data-table>
            @endif
        </section>

        <div class="mt-6 flex flex-wrap gap-3" data-print="hide">
            @foreach ($comparison->columns as $column)
                <x-button :href="route('tools.calculations.show', $column['uuid'])" variant="secondary" size="sm">
                    {{ $column['title'] }}
                </x-button>
            @endforeach
        </div>

        <x-disclaimer class="mt-6">
            مقایسه امتیازها نشان می‌دهد وضعیت ثبت‌شده چطور تغییر کرده است؛ تشخیص پزشکی یا تأیید قطعی
            ایمنی ایستگاه نیست و جای قضاوت کارشناس را نمی‌گیرد.
        </x-disclaimer>
    @endif

</x-layouts.workspace>
