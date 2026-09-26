@php
    use App\Modules\Reports\Domain\Enums\ReportStatus;
    use App\Support\JalaliDate;
    use App\Support\PersianDigits;
@endphp

<x-layouts.workspace :title="$report->title"
                     :heading="$report->title"
                     :lede="$report->revision > 1 ? 'نسخه '.PersianDigits::from($report->revision) : null"
                     nav="reports">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['گزارش‌ها', route('reports.index')], [$report->title, null]]" />
    </x-slot:breadcrumb>

    <x-slot:actions>
        <x-badge :tone="$report->status->tone()">{{ $report->status->label() }}</x-badge>
        <x-button :href="route('reports.download', $report->uuid)" variant="primary" icon="file">دانلود PDF</x-button>
    </x-slot:actions>

    @if (session('status'))
        <x-alert tone="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if ($errors->any())
        <x-alert tone="error" class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    @if ($report->status === ReportStatus::Superseded && $report->supersededBy)
        <x-alert tone="caution" class="mb-6" title="نسخه تازه‌تری صادر شده است">
            این گزارش با <a href="{{ route('reports.show', $report->supersededBy->uuid) }}">نسخه @fa($report->supersededBy->revision)</a>
            جایگزین شده و صفحه تأییدش همین را به گیرنده می‌گوید.
        </x-alert>
    @elseif ($report->status === ReportStatus::Revoked)
        <x-alert tone="error" class="mb-6" title="این گزارش باطل شده است">
            {{ JalaliDate::long($report->revoked_at ?? $report->updated_at) }} — {{ $report->revoke_reason }}
        </x-alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="شناسه رهگیری">
            <p dir="ltr" data-numeric class="text-h2 font-bold text-ink">{{ $report->tracking_code }}</p>
            <p class="mt-2 text-note text-muted">
                صادرشده در {{ JalaliDate::longWithTime($report->issued_at ?? $report->created_at) }}.
                گیرنده اصالت سند را با همین شناسه در صفحه تأیید بررسی می‌کند.
            </p>
            <p class="mt-3 text-note text-muted">هش SHA-256 فایل:</p>
            <p dir="ltr" data-numeric class="break-all text-note text-ink">{{ $report->pdf_sha256 }}</p>
            <div class="mt-4">
                <x-button :href="route('reports.verify.show', $report->tracking_code)" variant="secondary" size="sm" icon="shield">
                    صفحه تأیید اصالت
                </x-button>
            </div>
        </x-card>

        <x-card title="محتوا">
            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-label">
                <dt class="text-muted">منبع داده</dt><dd>{{ $document?->data->sourceTitle }}</dd>
                <dt class="text-muted">سطر نتیجه</dt><dd>@fa(count($document?->data->measurements ?? []))</dd>
                <dt class="text-muted">تجهیز ضمیمه</dt><dd>@fa(count($document?->data->equipment ?? []))</dd>
                <dt class="text-muted">تهیه‌کننده</dt><dd>{{ $document?->authorName }}</dd>
                @if ($report->supersedes)
                    <dt class="text-muted">جایگزین</dt>
                    <dd><a href="{{ route('reports.show', $report->supersedes->uuid) }}" dir="ltr">{{ $report->supersedes->tracking_code }}</a></dd>
                @endif
            </dl>
        </x-card>
    </div>

    @if ($report->status === ReportStatus::Issued)
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <x-card title="اصلاح گزارش">
                <p class="text-copy text-muted">
                    گزارش صادرشده ویرایش نمی‌شود. اصلاح یک پیش‌نویس تازه با همین متن می‌سازد؛ با صدور آن، این نسخه
                    «جایگزین‌شده» علامت می‌خورد و صفحه تأییدش به نسخه تازه اشاره می‌کند.
                </p>
                <form method="POST" action="{{ route('reports.revise', $report->uuid) }}" class="mt-4">
                    @csrf
                    <x-button type="submit" variant="secondary" icon="forward">
                        {{ $openRevision ? 'ادامه پیش‌نویس اصلاحی' : 'ساخت نسخه اصلاحی' }}
                    </x-button>
                </form>
            </x-card>

            <x-card title="ابطال گزارش">
                <p class="text-copy text-muted">
                    اگر این سند نباید دیگر معتبر شمرده شود، باطلش کنید. فایل حذف نمی‌شود؛ صفحه تأیید «باطل‌شده» نشان می‌دهد.
                </p>
                <form method="POST" action="{{ route('reports.revoke', $report->uuid) }}" class="mt-4 flex flex-col gap-3">
                    @csrf
                    <x-field name="reason" label="دلیل ابطال" required :error="$errors->first('reason')" />
                    <div>
                        <x-button type="submit" variant="danger">باطل کردن</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    @endif

</x-layouts.workspace>
