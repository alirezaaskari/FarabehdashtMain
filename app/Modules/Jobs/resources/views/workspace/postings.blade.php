@php
    use App\Modules\Jobs\Domain\Enums\PostingState;
    use App\Modules\Jobs\Domain\Enums\ReviewStatus;
    use App\Support\JalaliDate;
@endphp

<x-layouts.workspace art="employer-postings" title="آگهی‌های من" heading="آگهی‌های من"
                     lede="آگهی‌های شرکت شما، از پیش‌نویس تا منتشرشده و منقضی. هر آگهی و هر ویرایشش پیش از دیده‌شدن از تأیید مدیر می‌گذرد."
                     nav="employer-postings" help="employer-postings">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($company === null || ! $company->isListed())
        <x-empty-state art="empty-employer-postings" icon="briefcase" title="اول صفحه شرکت را بسازید"
                       description="آگهی زیر صفحه شرکت تأییدشده ثبت می‌شود. صفحه شرکت را بسازید و مدرک ثبت شرکت را بفرستید.">
            <x-slot:action>
                <x-button :href="route('jobs.company.edit')" variant="primary">ساخت صفحه شرکت</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-copy text-body">
                @if ($price->isZero())
                    انتشار آگهی بعدی شما رایگان است و @fa($days) روز در فهرست می‌ماند.
                @else
                    هر دوره انتشار {{ $price->format() }} برای @fa($days) روز است؛ پرداخت پس از تأیید مدیر.
                @endif
            </p>
            <x-button :href="route('jobs.employer.postings.create')" variant="primary" icon="plus">آگهی تازه</x-button>
        </div>

        @if ($postings->isEmpty())
            <p class="text-copy text-muted">هنوز آگهی‌ای نساخته‌اید.</p>
        @else
            <ul class="flex list-none flex-col divide-y divide-line rounded-xl border border-line bg-surface ps-0">
                @foreach ($postings as $posting)
                    @php $state = $posting->state(); $title = $posting->title ?? ($posting->pending['title'] ?? 'آگهی بی‌عنوان'); @endphp
                    <li class="flex flex-col gap-3 px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                @if ($posting->published_at)
                                    <a href="{{ route('jobs.show', $posting->id) }}" class="text-h4">{{ $title }}</a>
                                @else
                                    <p class="text-h4 text-ink">{{ $title }}</p>
                                @endif
                                <p class="mt-1 text-note text-muted">
                                    @if ($state === PostingState::Live && $posting->expires_at)
                                        تا {{ JalaliDate::long($posting->expires_at) }}
                                    @elseif ($posting->endedAt())
                                        پایان {{ JalaliDate::long($posting->endedAt()) }}
                                    @else
                                        ساخته‌شده {{ JalaliDate::long($posting->created_at) }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-badge :tone="$state->tone()">{{ $state->label() }}</x-badge>
                                @if ($posting->status === ReviewStatus::Pending)
                                    <x-badge tone="caution">{{ $posting->approved_at ? 'ویرایش در انتظار تأیید' : 'در انتظار تأیید' }}</x-badge>
                                @endif
                            </div>
                        </div>

                        @if ($posting->status === ReviewStatus::Rejected)
                            <x-alert tone="caution" title="برای اصلاح برگشت">{{ $posting->review_note }}</x-alert>
                        @endif

                        @if ($state !== PostingState::Closed)
                            <div class="flex flex-wrap gap-2">
                                @if (in_array($state, [PostingState::AwaitingPayment, PostingState::Live, PostingState::Expired], true))
                                    <x-button :href="route('jobs.employer.postings.checkout', $posting->uuid)" :variant="$state === PostingState::Live ? 'secondary' : 'primary'" size="sm">
                                        {{ $state === PostingState::AwaitingPayment ? 'انتشار' : 'تمدید' }}
                                    </x-button>
                                @endif
                                <x-button :href="route('jobs.employer.postings.edit', $posting->uuid)" variant="secondary" size="sm">ویرایش</x-button>
                                @if ($posting->published_at)
                                    <form method="POST" action="{{ route('jobs.employer.postings.close', $posting->uuid) }}">
                                        @csrf
                                        <x-button type="submit" variant="ghost" size="sm" aria-label="بستن آگهی {{ $title }}">بستن</x-button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    @endif

</x-layouts.workspace>
