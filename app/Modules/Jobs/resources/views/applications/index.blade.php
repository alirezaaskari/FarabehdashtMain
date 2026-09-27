@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="applications" title="درخواست‌های شغلی من" heading="درخواست‌های شغلی من"
                     lede="درخواست‌هایی که برای آگهی‌ها فرستاده‌اید، با وضعیت هر کدام و فهرست کارفرماهایی که شماره یا رزومه شما را دیده‌اند."
                     nav="applications" help="applications">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($applications->isEmpty())
        <x-empty-state art="empty-applications" icon="briefcase" title="هنوز درخواستی نفرستاده‌اید"
                       description="در فهرست آگهی‌ها شغل مناسب را پیدا کنید و از صفحه همان آگهی درخواست رایگان بفرستید.">
            <x-slot:action>
                <x-button :href="route('jobs.index')" variant="primary">دیدن آگهی‌ها</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <ul class="flex list-none flex-col gap-3 ps-0">
            @foreach ($applications as $application)
                <li>
                    <a href="{{ route('jobs.applications.show', $application->uuid) }}"
                       class="flex min-h-touch flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface px-5 py-4 no-underline hover:bg-surface-2 hover:no-underline">
                        <span class="flex min-w-0 flex-col gap-1">
                            <span class="text-h4 text-ink">{{ $application->posting->title }}</span>
                            <span class="text-note text-muted">{{ $application->posting->company->name }} · {{ JalaliDate::short($application->created_at) }}</span>
                        </span>
                        <x-badge :tone="$application->status->tone()">{{ $application->status->label() }}</x-badge>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    <x-card title="چه کسی اطلاعات شما را دیده" heading="text-h4" class="mt-8">
        @if ($accesses->isEmpty())
            <p class="text-copy text-muted">هنوز هیچ کارفرمایی شماره یا رزومه شما را باز نکرده است.</p>
        @else
            <ul class="flex list-none flex-col divide-y divide-line ps-0">
                @foreach ($accesses as $access)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-label">
                        <span class="text-body">{{ $access->company?->name ?? 'شرکت حذف‌شده' }} · {{ $access->kind->label() }} · {{ $access->source->label() }}</span>
                        <span class="text-note text-muted">{{ JalaliDate::long($access->created_at) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

</x-layouts.workspace>
