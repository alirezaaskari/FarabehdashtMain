@php
    use App\Modules\Marketplace\Domain\Enums\BidStatus;
    use App\Modules\Marketplace\Domain\Enums\MessageStatus;
    use App\Support\JalaliDate;
    use App\Support\Money;
@endphp

<x-layouts.workspace art="bid-thread" :title="'پیشنهاد: '.$project->title" :heading="$project->title"
                     :lede="$isProvider ? 'پیشنهاد شما و گفت‌وگو با کارفرما. هماهنگی فقط همین‌جاست؛ شماره و راه تماس رد و بدل نمی‌شود.' : 'پیشنهاد این مجری و گفت‌وگو با او. هماهنگی فقط همین‌جاست؛ شماره و راه تماس رد و بدل نمی‌شود.'"
                     :nav="$isProvider ? 'market-bids' : 'market-projects'" help="bid-thread">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @error('bid')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="flex min-w-0 flex-col gap-8">
            <section aria-labelledby="bid-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="bid-heading" class="text-h3 text-ink">پیشنهاد</h2>
                    <x-badge :tone="$bid->status->tone()">{{ $bid->status->label() }}</x-badge>
                </div>
                @foreach ($bid->coverParagraphs() as $paragraph)
                    <p class="mt-3 whitespace-pre-line text-copy text-body">{{ $paragraph }}</p>
                @endforeach

                <ol class="mt-5 flex list-none flex-col divide-y divide-line rounded-lg border border-line ps-0" aria-label="مرحله‌های پیشنهاد">
                    @foreach ($bid->milestones as $index => $milestone)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-label">
                            <span class="text-ink">@fa($index + 1). {{ $milestone['title'] }}</span>
                            <span class="text-body">{{ Money::toman((int) $milestone['amount_toman'])->format() }} · @fa($milestone['days']) روز</span>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-3 text-copy text-ink">جمع: {{ $bid->total()->format() }} در @fa($bid->total_days) روز کار</p>
            </section>

            <section aria-labelledby="messages-heading" id="messages">
                <h2 id="messages-heading" class="text-h3 text-ink">گفت‌وگو</h2>

                @if ($messages->isEmpty())
                    <p class="mt-3 text-copy text-muted">هنوز پیامی نیست. پرسش درباره روش کار، زمان حضور یا خروجی را همین‌جا بنویسید.</p>
                @else
                    <ol class="mt-4 flex list-none flex-col gap-3 ps-0">
                        @foreach ($messages as $message)
                            @php $mine = $message->sender_user_id === $userId; @endphp
                            <li class="rounded-lg border border-line p-4 {{ $mine ? 'bg-surface-2' : 'bg-surface' }}">
                                <p class="text-note text-muted">
                                    {{ $mine ? 'شما' : ($isProvider ? 'کارفرما' : ($provider['name'] ?? 'مجری')) }} · {{ JalaliDate::long($message->created_at) }}
                                </p>
                                <p class="mt-2 whitespace-pre-line text-copy text-body">{{ $message->body }}</p>
                                @if ($mine && $message->status === MessageStatus::Held)
                                    <p class="mt-2 text-note text-muted">در انتظار بررسی مدیر؛ پس از تأیید به طرف مقابل می‌رسد.</p>
                                @elseif ($mine && $message->status === MessageStatus::Rejected)
                                    <p class="mt-2 text-note font-semibold text-danger">رد شد و به طرف مقابل نرسید.</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if (in_array($bid->status, [BidStatus::Active, BidStatus::Accepted], true))
                    <form method="POST" action="{{ route('market.bids.message', $bid->uuid) }}" class="mt-5 flex flex-col gap-3">
                        @csrf
                        <label for="body" class="text-label font-semibold text-ink">پیام تازه</label>
                        <textarea id="body" name="body" rows="4" required maxlength="{{ $messageMax }}" aria-describedby="body-hint"
                                  @if ($errors->has('body')) aria-invalid="true" @endif
                                  class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('body') }}</textarea>
                        <p id="body-hint" class="text-note text-muted">
                            پیامی که شماره، ایمیل، لینک یا نام پیام‌رسان دارد تا بررسی مدیر نگه داشته می‌شود و اگر رد شود اخطار می‌گیرد.
                        </p>
                        @error('body')
                            <p class="text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                        <div><x-button type="submit" variant="primary">فرستادن</x-button></div>
                    </form>
                @endif
            </section>
        </div>

        <aside class="flex flex-col gap-5">
            <x-card title="پروژه" heading="text-h4">
                <dl class="flex flex-col gap-3 text-copy">
                    <div><dt class="text-note text-muted">نوع کار</dt><dd class="text-ink">{{ $catalog->serviceName($project->service) }}</dd></div>
                    <div><dt class="text-note text-muted">بودجه کارفرما</dt><dd class="text-ink">{{ $catalog->budgetLabel($project) }}</dd></div>
                    @if ($provider)
                        <div><dt class="text-note text-muted">مجری</dt><dd><a href="{{ $provider['url'] }}" class="inline-flex min-h-touch items-center">{{ $provider['name'] }}</a></dd></div>
                    @endif
                </dl>
                <div class="mt-5 flex flex-col gap-2">
                    @if ($contract)
                        <x-button :href="route('market.contracts.show', $contract->uuid)" variant="primary" block>صفحه قرارداد</x-button>
                    @endif
                    @if ($canAccept)
                        <form method="POST" action="{{ route('market.contracts.accept', $bid->uuid) }}">
                            @csrf
                            <x-button type="submit" variant="primary" block>پذیرش این پیشنهاد</x-button>
                        </form>
                        <p class="text-note text-muted">با پذیرش، مرحله‌ها و مبلغ قفل می‌شوند و پیشنهادهای دیگر بسته می‌شوند. پول هر مرحله را پیش از شروعش در امانت می‌گذارید.</p>
                    @endif
                    @if ($isProvider && $bid->status === BidStatus::Active)
                        <x-button :href="route('market.bid.create', $project->id)" variant="secondary" block>ویرایش پیشنهاد</x-button>
                        <form method="POST" action="{{ route('market.bids.withdraw', $bid->uuid) }}">
                            @csrf
                            <x-button type="submit" variant="ghost" block>پس‌گرفتن پیشنهاد</x-button>
                        </form>
                    @elseif (! $isProvider)
                        <x-button :href="route('market.client.show', $project->uuid)" variant="secondary" block>همه پیشنهادهای این پروژه</x-button>
                    @endif
                </div>
            </x-card>
        </aside>
    </div>

</x-layouts.workspace>
