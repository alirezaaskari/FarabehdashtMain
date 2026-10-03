@php
    use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
    use App\Support\JalaliDate;
@endphp

<x-layouts.workspace art="client-projects" title="پروژه‌های بازار من" heading="پروژه‌های بازار من"
                     lede="کارهایی که برای مشاوران و آزمایشگاه‌ها تعریف کرده‌اید. هر پروژه پیش از انتشار از تأیید مدیر می‌گذرد."
                     nav="market-projects" help="client-projects">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @error('project')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    @unless ($verified)
        <div class="mb-6">
            <x-alert tone="caution" title="اول موبایلتان را تأیید کنید">
                تعریف پروژه برای حسابی است که شماره موبایلش تأیید شده؛ از «حساب من» با کد پیامکی تأییدش کنید.
            </x-alert>
        </div>
    @endunless

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-copy text-body">تعریف پروژه رایگان است و نامتان پیش‌فرض در فهرست پنهان می‌ماند.</p>
        <x-button :href="route('market.client.create')" variant="primary" icon="plus">تعریف پروژه</x-button>
    </div>

    @if ($projects->isEmpty())
        <x-empty-state art="empty-client-projects" icon="briefcase" title="هنوز پروژه‌ای تعریف نکرده‌اید"
                       description="اندازه‌گیری، ارزیابی یا مستندسازی که به مشاور یا آزمایشگاه نیاز دارد را تعریف کنید تا پیشنهاد بگیرید." />
    @else
        <ul class="flex list-none flex-col divide-y divide-line rounded-xl border border-line bg-surface ps-0">
            @foreach ($projects as $project)
                <li class="flex flex-col gap-3 px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            @if ($project->status->isPublished())
                                <a href="{{ route('market.show', $project->id) }}" class="inline-flex min-h-touch items-center text-h4">{{ $project->title }}</a>
                            @else
                                <p class="text-h4 text-ink">{{ $project->title }}</p>
                            @endif
                            <p class="mt-1 text-note text-muted">
                                {{ $catalog->serviceName($project->service) }} · {{ $catalog->place($project) }} ·
                                @if ($project->status === ProjectStatus::Open && $project->bids_close_at)
                                    پیشنهاد تا {{ JalaliDate::long($project->bids_close_at) }}
                                @else
                                    ساخته‌شده {{ JalaliDate::long($project->created_at) }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge>
                            @if ($project->is_private)
                                <x-badge>خصوصی</x-badge>
                            @endif
                        </div>
                    </div>

                    @if ($project->status === ProjectStatus::Rejected)
                        <x-alert tone="caution" title="برای اصلاح برگشت">{{ $project->review_note }}</x-alert>
                    @endif

                    @if (in_array($project->status, [ProjectStatus::Pending, ProjectStatus::Rejected, ProjectStatus::Open], true))
                        <div class="flex flex-wrap gap-2">
                            @if ($project->status->isEditable())
                                <x-button :href="route('market.client.edit', $project->uuid)" variant="secondary" size="sm">ویرایش</x-button>
                            @endif
                            <form method="POST" action="{{ route('market.client.close', $project->uuid) }}">
                                @csrf
                                <x-button type="submit" variant="ghost" size="sm" aria-label="بستن پروژه {{ $project->title }}">بستن</x-button>
                            </form>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

</x-layouts.workspace>
