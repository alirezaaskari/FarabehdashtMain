@php
    use App\Support\PersianDigits;

    /** @var \App\Modules\Reports\Domain\ReportDocument $document */
    $data = $document->data;
    $equipment = collect($data->equipment)->keyBy('id');
    $blocking = $data->blockingEquipment();
    $groups = collect($data->measurements)->groupBy('group');
    $hasParameter = collect($data->measurements)->contains(fn ($m) => $m->parameter !== null);
@endphp

<div class="brand">فرابهداشت · گزارش اندازه‌گیری</div>
<h1>{{ $document->title }}</h1>

<table class="meta">
    @if ($document->clientName)
        <tr><td class="key">کارفرما</td><td>{{ $document->clientName }}</td></tr>
    @endif
    @if ($document->site)
        <tr><td class="key">محل اندازه‌گیری</td><td>{{ $document->site }}</td></tr>
    @endif
    @if ($document->measuredOn)
        <tr><td class="key">زمان اندازه‌گیری</td><td>{{ $document->measuredOn }}</td></tr>
    @endif
    <tr><td class="key">تهیه‌کننده</td><td>{{ $document->authorName }}</td></tr>
    <tr><td class="key">منبع داده</td><td>{{ $data->sourceTitle }}</td></tr>
    @if ($document->trackingCode)
        <tr><td class="key">شناسه رهگیری</td><td><span dir="ltr">{{ $document->trackingCode }}</span></td></tr>
        <tr><td class="key">تاریخ صدور</td><td>{{ $document->issuedOn }}</td></tr>
    @endif
    @if ($document->revision > 1)
        <tr>
            <td class="key">نسخه</td>
            <td>
                نسخه {{ PersianDigits::from($document->revision) }}
                @if ($document->supersedesCode)
                    — جایگزین گزارش <span dir="ltr">{{ $document->supersedesCode }}</span>
                @endif
            </td>
        </tr>
    @endif
</table>

<h2>نتایج اندازه‌گیری</h2>

<table class="grid">
    <thead>
        <tr>
            <th>نقطه</th>
            @if ($hasParameter)<th>پارامتر</th>@endif
            <th>مقدار</th>
            <th>تاریخ</th>
            @if ($document->includeEquipment && $equipment->isNotEmpty())<th>تجهیز</th>@endif
        </tr>
    </thead>
    <tbody>
        @foreach ($groups as $group => $rows)
            <tr class="group"><td colspan="5">{{ $group }}</td></tr>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->point }}</td>
                    @if ($hasParameter)<td>{{ $row->parameter ?? '—' }}</td>@endif
                    <td><span dir="ltr">{{ $row->value }}{{ $row->unit ? ' '.$row->unit : '' }}</span></td>
                    <td>{{ $row->measuredOn ?? '—' }}</td>
                    @if ($document->includeEquipment && $equipment->isNotEmpty())
                        <td>{{ $row->equipmentId !== null ? ($equipment->get($row->equipmentId)?->name ?? '—') : '—' }}</td>
                    @endif
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
<p class="muted">خانه «—» یعنی آن مورد ثبت نشده است، نه اینکه مقدارش صفر باشد.</p>

@if ($document->includeMethod)
    <h2>روش محاسبه</h2>
    @if ($data->formulas() === [])
        <p>همه مقادیر این گزارش قرائت مستقیم دستگاه‌اند و از فرمولی عبور نکرده‌اند.</p>
    @else
        <p>مقادیر محاسبه‌شده با این فرمول‌ها و نسخه‌ها به دست آمده‌اند؛ نسخه فرمول در فرابهداشت هرگز ویرایش نمی‌شود و همین مقادیر با همین نسخه بازتولیدپذیرند.</p>
        <table class="grid">
            @foreach ($data->formulas() as $formula)
                <tr><td><span dir="ltr">{{ $formula }}</span></td></tr>
            @endforeach
        </table>
    @endif
@endif

@if ($document->includeEquipment && $data->equipment !== [])
    <h2>تجهیزات به‌کاررفته</h2>
    <table class="grid">
        <thead>
            <tr>
                <th>تجهیز</th>
                <th>مدل و سریال</th>
                <th>کلاس دقت</th>
                <th>کالیبراسیون</th>
                <th>وضعیت</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data->equipment as $item)
                <tr>
                    <td>{{ $item->name }}@if ($item->manufacturer)<br><span class="muted">{{ $item->manufacturer }}</span>@endif</td>
                    <td><span dir="ltr">{{ trim(($item->model ?? '').' '.($item->serialNumber ?? '')) ?: '—' }}</span></td>
                    <td>{{ $item->accuracyClass ?? '—' }}</td>
                    <td>
                        @if ($item->calibratedOn) از {{ $item->calibratedOn }} @endif
                        @if ($item->validUntil) تا {{ $item->validUntil }} @endif
                        @if (! $item->calibratedOn && ! $item->validUntil) — @endif
                        @if ($item->calibrationReference)<br><span class="muted">{{ $item->calibrationReference }}</span>@endif
                    </td>
                    <td @if ($item->blocking) class="flag" @endif>{{ $item->calibrationStatus }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="muted">مشخصات تجهیزات از دفترچه تجهیزات تهیه‌کننده برداشته شده و جایگزین گواهی کالیبراسیون رسمی نیست.</p>

    @if ($blocking !== [] && $document->calibrationAcknowledged)
        <div class="note">
            برای {{ PersianDigits::from(count($blocking)) }} تجهیز این گزارش، کالیبراسیون معتبری ثبت نشده بود.
            تهیه‌کننده با علم به این موضوع گزارش را صادر کرده است.
        </div>
    @endif
@endif

@if ($document->findings)
    <h2>یافته‌ها</h2>
    <p>{!! nl2br(e($document->findings)) !!}</p>
@endif

@if ($document->recommendations)
    <h2>توصیه‌ها</h2>
    <p>{!! nl2br(e($document->recommendations)) !!}</p>
@endif

@if ($verifyUrl)
    <table class="verify" style="margin-top: 8mm;">
        <tr>
            <td style="width: 28mm;"><barcode code="{{ $verifyUrl }}" type="QR" size="0.9" error="M" disableborder="1" /></td>
            <td>
                <p>اصالت این گزارش را با شناسه رهگیری زیر در صفحه تأیید فرابهداشت بررسی کنید. آن صفحه فقط تأیید می‌کند این سند بدون تغییر از فرابهداشت صادر شده است.</p>
                <p dir="ltr" style="text-align: left;">{{ $document->trackingCode }} · {{ $verifyUrl }}</p>
            </td>
        </tr>
    </table>
@endif

<div class="disclaimer">
    این گزارش را تهیه‌کننده بر اساس داده‌هایی که خودش ثبت کرده ساخته است و درستی اندازه‌گیری‌ها و تفسیر آن‌ها
    بر عهده اوست. هیچ بخشی از این گزارش تشخیص پزشکی، تأیید قطعی ایمنی یا گواهی انطباق با الزامات قانونی نیست
    و فرابهداشت مرجع صدور گواهی یا تأیید صلاحیت حرفه‌ای نیست.
</div>
