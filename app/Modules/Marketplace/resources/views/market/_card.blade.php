{{-- کارت یک پروژه باز در فهرست. نام کارفرما پیش‌فرض پنهان است (DEC-84). --}}
<li>
    <a href="{{ route('market.show', $project->id) }}"
       class="flex h-full min-h-touch flex-col gap-2 rounded-xl border border-line bg-surface px-5 py-5 no-underline hover:bg-surface-2 hover:no-underline">
        <span class="text-h4 text-ink">{{ $project->title }}</span>
        <span class="text-copy text-body">{{ $catalog->clientLabel($project) }}</span>
        <span class="flex flex-wrap items-center gap-2">
            <x-badge>{{ $catalog->serviceName($project->service) }}</x-badge>
            <x-badge icon="compass">{{ $catalog->place($project) }}</x-badge>
        </span>
        <span class="text-note text-muted">بودجه {{ $catalog->budgetLabel($project) }}</span>
    </a>
</li>
