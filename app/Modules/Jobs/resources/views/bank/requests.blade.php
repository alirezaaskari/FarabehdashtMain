@php
    use App\Modules\Jobs\Domain\Enums\BankRequestStatus;
    use App\Support\JalaliDate;
@endphp

<x-layouts.workspace art="talent-requests" title="درخواست‌های تماس بانک رزومه" heading="درخواست‌های تماس بانک رزومه"
                     lede="درخواست‌هایی که از بانک رزومه فرستاده‌اید. نام و راه تماس فقط پس از پذیرش کارجو و با دکمه «نمایش» می‌آید و کارجو فهرست این دیدن‌ها را دارد."
                     nav="talent" help="talent-requests">

    @error('bank')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    <div class="grid items-start gap-8 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-labelledby="sent-heading" class="min-w-0">
            <h2 id="sent-heading" class="text-h3 text-ink">فرستاده‌شده</h2>

            @if ($requests->isEmpty())
                <x-empty-state art="empty-talent-requests" icon="bell" class="mt-4" title="هنوز درخواستی نفرستاده‌اید"
                               description="در بانک رزومه کارت کارجوی مناسب را پیدا کنید و «درخواست تماس» را بزنید.">
                    <x-slot:action>
                        <x-button :href="route('jobs.talent.index')" variant="primary">جست‌وجو در بانک</x-button>
                    </x-slot:action>
                </x-empty-state>
            @else
                <ul class="mt-4 flex list-none flex-col gap-3 ps-0">
                    @foreach ($requests as $bankRequest)
                        <li class="flex flex-col gap-3 rounded-xl border border-line bg-surface px-5 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-h4 text-ink">
                                    {{ $bankRequest->status === BankRequestStatus::Accepted ? ($bankRequest->jobseeker->name ?: 'کارجوی فرابهداشت') : 'کارجوی ناشناس' }}
                                </span>
                                <x-badge :tone="$bankRequest->status->tone()">{{ $bankRequest->status->label() }}</x-badge>
                            </div>
                            <p class="text-note text-muted">
                                فرستاده {{ JalaliDate::long($bankRequest->created_at) }}
                                @if ($bankRequest->status === BankRequestStatus::Pending)
                                    · مهلت پاسخ تا {{ JalaliDate::long($bankRequest->expires_at) }}
                                @elseif ($bankRequest->charged && ! $bankRequest->status->holdsCredit())
                                    · اعتبار برگشت
                                @endif
                            </p>
                            @if ($bankRequest->status === BankRequestStatus::Accepted)
                                @if ($shown === $bankRequest->uuid)
                                    <dl class="flex flex-col gap-2 text-label">
                                        <div><dt class="text-note text-muted">موبایل</dt><dd class="text-ink" data-numeric>{{ $bankRequest->jobseeker->mobile }}</dd></div>
                                        @if ($bankRequest->jobseeker->email)
                                            <div><dt class="text-note text-muted">ایمیل</dt><dd class="text-ink" data-numeric>{{ $bankRequest->jobseeker->email }}</dd></div>
                                        @endif
                                    </dl>
                                @else
                                    <form method="POST" action="{{ route('jobs.talent.reveal', $bankRequest->uuid) }}">
                                        @csrf
                                        <x-button type="submit" variant="secondary" size="sm">نمایش شماره و ایمیل</x-button>
                                    </form>
                                @endif
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="flex flex-col gap-6">
            @include('jobs::bank._billing')
            <x-button :href="route('jobs.talent.index')" variant="secondary" block>جست‌وجو در بانک</x-button>
        </div>
    </div>

</x-layouts.workspace>
