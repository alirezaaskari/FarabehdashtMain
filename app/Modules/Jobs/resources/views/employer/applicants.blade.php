@php
    use App\Modules\Jobs\Domain\Enums\ApplicationStatus;
    use App\Support\JalaliDate;
@endphp

<x-layouts.workspace art="applicants" title="متقاضیان" :heading="'متقاضیان: '.$posting->title"
                     lede="درخواست‌های رسیده برای همین آگهی. باز کردن هر درخواست آن را «دیده‌شده» می‌کند و کارجو خبر می‌گیرد."
                     nav="employer-postings" help="applicants">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['آگهی‌های من', route('jobs.employer.postings.index')], [$posting->title, null]]" />
    </x-slot:breadcrumb>

    @if ($applications->isEmpty())
        <x-empty-state art="empty-applicants" icon="briefcase" title="هنوز درخواستی نرسیده"
                       description="وقتی کارجویی برای این آگهی درخواست بفرستد، همین‌جا می‌آید و اعلان می‌گیرید." />
    @else
        <p class="mb-4 text-copy text-body">
            @fa($applications->count()) درخواست؛
            @fa($counts['received'] ?? 0) تازه، @fa($counts['shortlisted'] ?? 0) در فهرست کوتاه.
        </p>
        <ul class="flex list-none flex-col gap-3 ps-0">
            @foreach ($applications as $application)
                <li>
                    @if ($application->status === ApplicationStatus::Withdrawn)
                        <div class="flex min-h-touch flex-wrap items-center justify-between gap-3 rounded-xl border border-line px-5 py-4">
                            <span class="text-copy text-muted">درخواستی که کارجو پس گرفت · {{ JalaliDate::short($application->created_at) }}</span>
                            <x-badge :tone="$application->status->tone()">{{ $application->status->label() }}</x-badge>
                        </div>
                    @else
                    <a href="{{ route('jobs.employer.applicants.show', $application->uuid) }}"
                       class="flex min-h-touch flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface px-5 py-4 no-underline hover:bg-surface-2 hover:no-underline">
                        <span class="flex min-w-0 flex-col gap-1">
                            <span class="text-h4 text-ink">{{ $application->user->name ?: 'کارجوی فرابهداشت' }}</span>
                            <span class="text-note text-muted">{{ JalaliDate::short($application->created_at) }}</span>
                        </span>
                        <x-badge :tone="$application->status->tone()">{{ $application->status->label() }}</x-badge>
                    </a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

</x-layouts.workspace>
