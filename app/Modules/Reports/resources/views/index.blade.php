@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="گزارش‌ها"
                     heading="گزارش‌ها"
                     lede="از یک پروژه اندازه‌گیری یا چند محاسبه ذخیره‌شده، گزارش PDF با شناسه رهگیری و صفحه تأیید اصالت بسازید."
                     nav="reports">

    <x-slot:actions>
        <x-button :href="route('reports.create')" variant="primary" icon="plus">گزارش تازه</x-button>
    </x-slot:actions>

    @if (session('status'))
        <x-alert tone="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if ($reports->isEmpty())
        <x-empty-state icon="file"
                       title="هنوز گزارشی نساخته‌اید"
                       description="گزارش‌ساز جدول نتایج، مشخصات تجهیزات و نسخه فرمول‌ها را خودکار می‌چیند؛ شما فقط یافته‌ها را می‌نویسید.">
            <x-slot:action>
                <x-button :href="route('reports.create')" variant="primary" icon="plus">ساخت اولین گزارش</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <x-data-table :headers="['عنوان', 'وضعیت', 'شناسه رهگیری', 'آخرین تغییر']" caption="گزارش‌های شما، تازه‌ترین اول">
            @foreach ($reports as $report)
                <tr>
                    <td>
                        <a href="{{ route('reports.show', $report->uuid) }}" class="inline-flex min-h-touch items-center font-semibold text-ink">
                            {{ $report->title ?? 'گزارش بی‌عنوان' }}
                        </a>
                        @if ($report->revision > 1)
                            <span class="text-note text-muted">· نسخه @fa($report->revision)</span>
                        @endif
                    </td>
                    <td><x-badge :tone="$report->status->tone()">{{ $report->status->label() }}</x-badge></td>
                    <td>
                        @if ($report->tracking_code)
                            <span dir="ltr" data-numeric>{{ $report->tracking_code }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ JalaliDate::short($report->updated_at) }}</td>
                </tr>
            @endforeach
        </x-data-table>
    @endif

</x-layouts.workspace>
