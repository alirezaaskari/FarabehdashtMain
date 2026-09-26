@php
    use App\Modules\Reports\Domain\Enums\ReportStatus;
    use App\Support\JalaliDate;
@endphp

<x-layouts.public :title="'تأیید اصالت '.$code"
                  description="وضعیت گزارشی که فرابهداشت با این شناسه رهگیری صادر کرده است."
                  noindex>

    <x-page-header title="تأیید اصالت گزارش">
        <x-slot:meta>
            <span dir="ltr" data-numeric class="text-h4 font-semibold text-ink">{{ $code }}</span>
        </x-slot:meta>
    </x-page-header>

    <div class="mt-8">
        @if ($report === null)
            <x-alert tone="error" title="گزارشی با این شناسه صادر نشده است">
                شناسه را با پانویس گزارش مقایسه کنید. اگر درست وارد شده و باز هم پیدا نمی‌شود، این سند از فرابهداشت
                صادر نشده است.
            </x-alert>
        @elseif ($report->status === ReportStatus::Issued)
            <x-alert tone="success" title="این گزارش معتبر است و آخرین نسخه آن است">
                سندی با این شناسه از فرابهداشت صادر شده و پس از آن نه باطل شده و نه نسخه تازه‌ای جایش را گرفته است.
            </x-alert>
        @elseif ($report->status === ReportStatus::Superseded)
            <x-alert tone="caution" title="نسخه تازه‌تری از این گزارش صادر شده است">
                این سند صادر شده بود ولی تهیه‌کننده آن را اصلاح کرده است.
                @if ($report->supersededBy?->tracking_code)
                    نسخه جاری: <a href="{{ route('reports.verify.show', $report->supersededBy->tracking_code) }}" dir="ltr">{{ $report->supersededBy->tracking_code }}</a>
                @endif
            </x-alert>
        @else
            <x-alert tone="error" title="این گزارش باطل شده است">
                این سند از فرابهداشت صادر شده بود ولی در {{ JalaliDate::long($report->revoked_at ?? $report->updated_at) }}
                باطل شد و دیگر نباید به آن استناد شود.
            </x-alert>
        @endif
    </div>

    @if ($report !== null && $document !== null)
        <x-card class="mt-6" title="مشخصات سند">
            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-3 text-label">
                <dt class="text-muted">عنوان</dt><dd class="text-ink">{{ $document->title }}</dd>
                <dt class="text-muted">تهیه‌کننده</dt><dd class="text-ink">{{ $document->authorName }}</dd>
                <dt class="text-muted">تاریخ صدور</dt><dd class="text-ink">{{ $document->issuedOn }}</dd>
                <dt class="text-muted">سطر نتیجه</dt><dd class="text-ink">@fa(count($document->data->measurements))</dd>
                <dt class="text-muted">هش SHA-256</dt>
                <dd dir="ltr" data-numeric class="break-all text-note text-ink">{{ $report->pdf_sha256 }}</dd>
            </dl>
            <p class="mt-4 text-note text-muted">
                برای اطمینان از اینکه فایل دست شما همان فایل صادرشده است، هش SHA-256 آن را با این مقدار مقایسه کنید.
                محتوای گزارش و نام کارفرما این‌جا نمایش داده نمی‌شود.
            </p>
        </x-card>
    @endif

    <x-disclaimer class="mt-8">
        این صفحه فقط تأیید می‌کند که سند بدون تغییر از فرابهداشت صادر شده است؛ درستی اندازه‌گیری‌ها و
        تفسیر آن‌ها بر عهده تهیه‌کننده گزارش است.
    </x-disclaimer>

    <div class="mt-6">
        <x-button :href="route('reports.verify.form')" variant="secondary" icon="search">بررسی شناسه دیگر</x-button>
    </div>

</x-layouts.public>
