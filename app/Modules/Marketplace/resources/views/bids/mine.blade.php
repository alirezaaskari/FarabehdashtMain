@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="provider-bids" title="پیشنهادهای بازار من" heading="پیشنهادهای بازار من"
                     lede="پیشنهادهایی که روی پروژه‌های بازار داده‌اید و دعوت‌هایی که کارفرماها برایتان فرستاده‌اند."
                     nav="market-bids" help="provider-bids">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($blocked)
        <div class="mb-6">
            <x-alert tone="error" title="دسترسی پیشنهاد بسته است">
                چند پیام شما به‌خاطر تلاش برای ردوبدل راه تماس رد شد. تا مدیر دوباره باز نکند، پیشنهاد تازه یا پروژه تازه ثبت نمی‌شود.
            </x-alert>
        </div>
    @endif

    @if ($invites->isNotEmpty())
        <section aria-labelledby="invites-heading" class="mb-10">
            <h2 id="invites-heading" class="mb-4 text-h3 text-ink">دعوت‌های تازه</h2>
            <ul class="flex list-none flex-col divide-y divide-line rounded-xl border border-line bg-surface ps-0">
                @foreach ($invites as $invite)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div class="min-w-0">
                            <a href="{{ route('market.show', $invite->project->id) }}" class="inline-flex min-h-touch items-center text-h4">{{ $invite->project->title }}</a>
                            <p class="text-note text-muted">{{ $catalog->serviceName($invite->project->service) }} · {{ $catalog->place($invite->project) }} · {{ $catalog->budgetLabel($invite->project) }}</p>
                        </div>
                        <x-button :href="route('market.bid.create', $invite->project->id)" variant="primary" size="sm">ثبت پیشنهاد</x-button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section aria-labelledby="bids-heading">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 id="bids-heading" class="text-h3 text-ink">پیشنهادهای من</h2>
            <x-button :href="route('market.index')" variant="secondary" icon="search">پروژه‌های باز</x-button>
        </div>

        @if ($bids->isEmpty())
            <x-empty-state art="empty-provider-bids" icon="briefcase" title="هنوز پیشنهادی نداده‌اید"
                           description="پروژه‌های باز بازار را ببینید و روی کاری که در توانتان است با مرحله و مبلغ روشن پیشنهاد بدهید. دادن پیشنهاد رایگان است." />
        @else
            <ul class="flex list-none flex-col divide-y divide-line rounded-xl border border-line bg-surface ps-0">
                @foreach ($bids as $bid)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div class="min-w-0">
                            <a href="{{ route('market.bids.show', $bid->uuid) }}" class="inline-flex min-h-touch items-center text-h4">{{ $bid->project->title }}</a>
                            <p class="text-note text-muted">{{ $bid->total()->format() }} · @fa($bid->total_days) روز · به‌روز {{ JalaliDate::long($bid->updated_at) }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :tone="$bid->status->tone()">{{ $bid->status->label() }}</x-badge>
                            @if ($bid->contract)
                                <x-button :href="route('market.contracts.show', $bid->contract->uuid)" variant="primary" size="sm">صفحه قرارداد</x-button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

</x-layouts.workspace>
