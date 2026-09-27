{{-- کارت یک آگهی در فهرست. حقوق نداشتن آگهی را پایین‌تر نمی‌برد (DEC-67). --}}
<li>
    <a href="{{ route('jobs.show', $posting->id) }}"
       class="flex h-full min-h-touch flex-col gap-2 rounded-xl border border-line bg-surface px-5 py-5 no-underline hover:bg-surface-2 hover:no-underline">
        <span class="text-h4 text-ink">{{ $posting->title }}</span>
        <span class="text-copy text-body">{{ $posting->company->name }}</span>
        <span class="flex flex-wrap items-center gap-2">
            <x-badge icon="compass">{{ $catalog->place($posting->province, $posting->city) }}</x-badge>
            <x-badge>{{ $posting->employment_type?->label() }}</x-badge>
        </span>
        <span class="text-note text-muted">{{ $catalog->salaryLabel($posting) }} · {{ $catalog->experienceLabel($posting->min_experience_years) }}</span>
    </a>
</li>
