@php
    use App\Support\PersianDigits;

    /** @var \App\Modules\Reports\Domain\ReportDocument $document */
    $data = $document->data;
    $equipment = collect($data->equipment)->keyBy('id');
    $groups = collect($data->measurements)->groupBy('group');
    $hasParameter = collect($data->measurements)->contains(fn ($m) => $m->parameter !== null);
    $showEquipment = $document->includeEquipment && $equipment->isNotEmpty();
    $cell = 'border border-line px-3 py-2 text-start align-top';
@endphp

{{--
    نسخه وب همان سند PDF، برای بررسی متخصص (بخش ۱۹-۴). ساختار و ترتیب بخش‌ها
    با pdf/document یکی است؛ هر بخش شناسه `report-{کلید}` دارد تا یادداشت
    بررسی‌کننده به آن اشاره کند.
--}}
<article class="flex flex-col gap-6 text-copy text-ink" aria-label="متن گزارش {{ $document->title }}">
    <section id="report-meta" class="flex flex-col gap-3">
        <h3 class="text-h3">{{ $document->title }}</h3>
        <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-label">
            @if ($document->clientName)<dt class="text-muted">کارفرما</dt><dd>{{ $document->clientName }}</dd>@endif
            @if ($document->site)<dt class="text-muted">محل اندازه‌گیری</dt><dd>{{ $document->site }}</dd>@endif
            @if ($document->measuredOn)<dt class="text-muted">زمان اندازه‌گیری</dt><dd>{{ $document->measuredOn }}</dd>@endif
            <dt class="text-muted">تهیه‌کننده</dt><dd>{{ $document->authorName }}</dd>
            <dt class="text-muted">منبع داده</dt><dd>{{ $data->sourceTitle }}</dd>
            @if ($document->trackingCode)
                <dt class="text-muted">شناسه رهگیری</dt><dd dir="ltr" data-numeric class="text-start">{{ $document->trackingCode }}</dd>
                <dt class="text-muted">تاریخ صدور</dt><dd>{{ $document->issuedOn }}</dd>
            @endif
            @if ($document->revision > 1)
                <dt class="text-muted">نسخه</dt><dd>{{ PersianDigits::from($document->revision) }}</dd>
            @endif
        </dl>
    </section>

    <section id="report-results" class="flex flex-col gap-3">
        <h3 class="text-h4">نتایج اندازه‌گیری</h3>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-label">
                <thead class="bg-surface-2">
                    <tr>
                        <th scope="col" class="{{ $cell }}">نقطه</th>
                        @if ($hasParameter)<th scope="col" class="{{ $cell }}">پارامتر</th>@endif
                        <th scope="col" class="{{ $cell }}">مقدار</th>
                        <th scope="col" class="{{ $cell }}">تاریخ</th>
                        @if ($showEquipment)<th scope="col" class="{{ $cell }}">تجهیز</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as $group => $rows)
                        <tr><th scope="rowgroup" colspan="5" class="{{ $cell }} bg-surface-2">{{ $group }}</th></tr>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="{{ $cell }}">{{ $row->point }}</td>
                                @if ($hasParameter)<td class="{{ $cell }}">{{ $row->parameter ?? '—' }}</td>@endif
                                <td class="{{ $cell }}"><span dir="ltr" data-numeric>{{ $row->value }}{{ $row->unit ? ' '.$row->unit : '' }}</span></td>
                                <td class="{{ $cell }}">{{ $row->measuredOn ?? '—' }}</td>
                                @if ($showEquipment)
                                    <td class="{{ $cell }}">{{ $row->equipmentId !== null ? ($equipment->get($row->equipmentId)?->name ?? '—') : '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($data->assessments !== [])
        <section id="report-assessments" class="flex flex-col gap-3">
            <h3 class="text-h4">ارزیابی ارگونومی</h3>
            @foreach ($data->assessments as $assessment)
                <div class="flex flex-col gap-2">
                    <h4 class="text-label font-bold">{{ $assessment->point }} · {{ $assessment->method }}</h4>
                    @foreach ($assessment->notes as $note)
                        <p>{{ $note }}</p>
                    @endforeach
                    @if ($assessment->answers !== [])
                        <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-1 text-label">
                            @foreach ($assessment->answers as $answer)
                                <dt class="text-muted">{{ $answer['label'] }}</dt><dd>{{ $answer['value'] }}</dd>
                            @endforeach
                        </dl>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    @if ($document->includeMethod)
        <section id="report-method" class="flex flex-col gap-3">
            <h3 class="text-h4">روش محاسبه</h3>
            @if ($data->formulas() === [])
                <p>همه مقادیر این گزارش قرائت مستقیم دستگاه‌اند و از فرمولی عبور نکرده‌اند.</p>
            @else
                <ul class="flex list-none flex-col gap-1 ps-0">
                    @foreach ($data->formulas() as $formula)
                        <li dir="ltr" data-numeric class="text-start">{{ $formula }}</li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    @if ($document->includeEquipment && $data->equipment !== [])
        <section id="report-equipment" class="flex flex-col gap-3">
            <h3 class="text-h4">تجهیزات به‌کاررفته</h3>
            <ul class="flex list-none flex-col gap-2 ps-0">
                @foreach ($data->equipment as $item)
                    <li class="rounded-lg border border-line px-4 py-3 text-label">
                        <span class="font-bold">{{ $item->name }}</span>
                        @if ($item->model || $item->serialNumber)
                            · <span dir="ltr" data-numeric>{{ trim(($item->model ?? '').' '.($item->serialNumber ?? '')) }}</span>
                        @endif
                        <span class="block text-muted">
                            کالیبراسیون: {{ $item->calibrationStatus }}
                            @if ($item->validUntil) · تا {{ $item->validUntil }} @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($document->findings)
        <section id="report-findings" class="flex flex-col gap-3">
            <h3 class="text-h4">یافته‌ها</h3>
            <p>{!! nl2br(e($document->findings)) !!}</p>
        </section>
    @endif

    @if ($document->recommendations)
        <section id="report-recommendations" class="flex flex-col gap-3">
            <h3 class="text-h4">توصیه‌ها</h3>
            <p>{!! nl2br(e($document->recommendations)) !!}</p>
        </section>
    @endif
</article>
