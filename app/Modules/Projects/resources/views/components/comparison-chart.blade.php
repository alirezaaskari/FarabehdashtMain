@props(['chart', 'comparison'])

{{--
    نمودار میله‌ای جفتی مقایسه دو دور.

    قواعدی که این‌جا رعایت شده‌اند و اتفاقی نیستند:
    · راهنمای رنگ همیشه حاضر است — هویت هرگز فقط با رنگ منتقل نمی‌شود.
    · هر میله برچسب عددی مستقیم دارد؛ همین برچسب به‌علاوه فاصله بین دو میله،
      کدگذاری ثانویه‌ای است که جدایی رنگ‌ها را برای کوررنگی کافی می‌کند.
    · جدول عددی زیر نمودار می‌آید، پس خروجی چاپی بدون رنگ هم خوانا است.
    · نمودار در چاپ حذف می‌شود و جدول می‌ماند.
    · هیچ رنگ هگزی این‌جا نیست؛ fill-chart-1 و fill-chart-2 توکن معنایی‌اند
      که در حالت تاریک مقدار دیگری می‌گیرند (نه وارونه‌شدن خودکار).
--}}

@php
    $bars = $chart->bars($comparison);
    $labels = $chart->stationLabels($comparison);
    $grid = $chart->gridlines($comparison);
@endphp

<figure class="m-0">
    <figcaption class="mb-3 flex flex-wrap items-center gap-4">
        @foreach ([[1, $comparison->before->title], [2, $comparison->after->title]] as [$series, $title])
            <span class="flex items-center gap-2 text-sm font-semibold text-ink">
                <span @class([
                    'inline-block h-3 w-3 rounded-sm',
                    'bg-chart-1' => $series === 1,
                    'bg-chart-2' => $series === 2,
                ]) aria-hidden="true"></span>
                {{ $title }}
            </span>
        @endforeach

        <span class="text-xs text-muted">
            واحد: <span dir="ltr" data-numeric>{{ $comparison->unit }}</span>
        </span>
    </figcaption>

    <div data-print="hide" class="overflow-x-auto rounded-xl border border-line bg-surface p-4">
        {{--
            فضای مختصات SVG ذاتاً چپ‌به‌راست است. بدون [direction:ltr]، جهت
            راست‌به‌چپ صفحه به ارث می‌رسد و text-anchor="start" لبه **راست** متن
            را لنگر می‌کند؛ نتیجه‌اش این بود که «96.4» از سمت چپ بیرون می‌زد و
            فقط «4» دیده می‌شد. برچسب‌های فارسی داخل نمودار مشکلی ندارند، چون
            الگوریتم دوجهته متن راست‌به‌چپ را داخل بستر چپ‌به‌راست درست می‌چیند.
        --}}
        <svg viewBox="0 0 {{ $chart->width() }} {{ $chart->height() }}"
             class="h-auto w-full min-w-[520px] [direction:ltr]"
             role="img"
             aria-label="نمودار مقایسه دو دور اندازه‌گیری به تفکیک ایستگاه. جدول عددی همین داده‌ها پایین‌تر آمده است.">

            @foreach ($grid as $line)
                <line x1="0" x2="{{ $chart->width() }}" y1="{{ $line['y'] }}" y2="{{ $line['y'] }}"
                      class="stroke-line" stroke-width="1" />
                {{-- dir و text-anchor هر دو لازم‌اند: متن SVG جهت راست‌به‌چپ صفحه
                     را به ارث می‌برد و بدون این دو، «96.4» از سمت چپ بریده می‌شود
                     و فقط «4» دیده می‌شود. --}}
                <text x="6" y="{{ $line['y'] - 4 }}" dir="ltr" text-anchor="start"
                      class="fill-muted text-[10px]">{{ $line['label'] }}</text>
            @endforeach

            @foreach ($bars as $bar)
                <g>
                    <title>{{ $bar->station }} — {{ $bar->seriesLabel }}: {{ $bar->formattedValue }} {{ $comparison->unit }}</title>
                    <rect x="{{ $bar->x }}" y="{{ $bar->y }}"
                          width="{{ $bar->width }}" height="{{ max($bar->height, 1) }}"
                          rx="4" ry="4"
                          class="{{ $bar->chartClass() }}" />
                    <text x="{{ $bar->x + $bar->width / 2 }}" y="{{ $bar->y - 6 }}"
                          text-anchor="middle" dir="ltr"
                          class="fill-ink text-[11px] font-bold">{{ $bar->formattedValue }}</text>
                </g>
            @endforeach

            <line x1="0" x2="{{ $chart->width() }}" y1="{{ $chart->baselineY() }}" y2="{{ $chart->baselineY() }}"
                  class="stroke-line-strong" stroke-width="1" />

            @foreach ($labels as $label)
                <text x="{{ $label['x'] }}" y="{{ $chart->baselineY() + 20 }}"
                      text-anchor="middle"
                      class="fill-muted text-[11px]">{{ $label['label'] }}</text>
            @endforeach
        </svg>
    </div>
</figure>
