@php
    use App\Modules\Marketplace\Domain\Enums\BidStatus;
    use App\Support\JalaliDate;
@endphp

<x-layouts.workspace art="client-project-bids" :title="'پیشنهادها: '.$project->title" :heading="$project->title"
                     lede="پیشنهادهای مجری‌ها کنار هم، از ارزان‌ترین. متن، مرحله‌ها و گفت‌وگوی هر کدام را باز کنید و پیش از انتخاب بپرسید."
                     nav="market-projects" help="client-project-show">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge>
        <x-badge>{{ $catalog->serviceName($project->service) }}</x-badge>
        <x-badge icon="compass">{{ $catalog->place($project) }}</x-badge>
        <span class="text-note text-muted">بودجه {{ $catalog->budgetLabel($project) }}
            @if ($project->acceptsBids() && $project->bids_close_at) · پیشنهاد تا {{ JalaliDate::long($project->bids_close_at) }} @endif
        </span>
    </div>

    @if ($bids->isEmpty())
        <x-empty-state art="empty-client-project-bids" icon="briefcase" title="هنوز پیشنهادی نرسیده"
                       :description="$project->status->isPublished()
                           ? 'مشاوران و آزمایشگاه‌های هم‌خدمت و هم‌شهر از پروژه شما خبر گرفته‌اند. از صفحه یک مشاور یا آزمایشگاه در دایرکتوری هم می‌توانید مستقیم دعوتش کنید.'
                           : 'پروژه پس از تأیید مدیر منتشر می‌شود و آن وقت پیشنهادها اینجا می‌آیند.'">
            @if ($project->acceptsBids() && Route::has('consulting.directory.index'))
                <x-slot:action>
                    <x-button :href="route('consulting.directory.index')" variant="secondary">دیدن دایرکتوری</x-button>
                </x-slot:action>
            @endif
        </x-empty-state>
    @else
        <ul class="flex list-none flex-col gap-4 ps-0">
            @foreach ($bids as $bid)
                @php $provider = $providers[$bid->provider_user_id] ?? null; @endphp
                <li class="rounded-xl border border-line bg-surface p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-h4 text-ink">
                                @if ($provider)
                                    <a href="{{ $provider['url'] }}" class="inline-flex min-h-touch items-center">{{ $provider['name'] }}</a>
                                @else
                                    مجری فرابهداشت
                                @endif
                            </h2>
                            <p class="text-note text-muted">
                                {{ $provider && $provider['laboratory'] ? 'آزمایشگاه' : 'مشاور' }}@if ($provider && $catalog->cityName($provider['city'])) · {{ $catalog->cityName($provider['city']) }}@endif
                            </p>
                            @php $record = $records[$bid->provider_user_id] ?? null; @endphp
                            <p class="text-note text-muted">
                                @if ($record === null || $record->isEmpty())
                                    هنوز پروژه‌ای در بازار تحویل نداده
                                @else
                                    @fa($record->completed) پروژه تحویل‌شده@if ($record->average !== null) · میانگین امتیاز {{ $record->averageLabel() }} از ۵ (@fa($record->ratings))@endif
                                @endif
                            </p>
                        </div>
                        <div class="text-end">
                            <p class="text-h4 text-ink">{{ $bid->total()->format() }}</p>
                            <p class="text-note text-muted">@fa(count($bid->milestones)) مرحله · @fa($bid->total_days) روز</p>
                        </div>
                    </div>
                    <p class="mt-3 text-copy text-body">{{ Str::limit($bid->cover, 240) }}</p>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        @if ($bid->status === BidStatus::Accepted)
                            <x-badge :tone="$bid->status->tone()">{{ $bid->status->label() }}</x-badge>
                        @endif
                        <x-button :href="route('market.bids.show', $bid->uuid)" variant="secondary" size="sm">پیشنهاد کامل و گفت‌وگو</x-button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($invites->isNotEmpty())
        <section aria-labelledby="invites-heading" class="mt-10">
            <h2 id="invites-heading" class="text-h3 text-ink">دعوت‌شده‌ها</h2>
            <ul class="mt-3 flex list-none flex-col gap-1 ps-0 text-copy text-body">
                @foreach ($invites as $invite)
                    <li>{{ $providers[$invite->provider_user_id]['name'] ?? 'مجری فرابهداشت' }} · {{ JalaliDate::long($invite->created_at) }}</li>
                @endforeach
            </ul>
        </section>
    @endif

</x-layouts.workspace>
