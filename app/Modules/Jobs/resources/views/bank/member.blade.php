@php
    use App\Modules\Jobs\Domain\Enums\BankRequestStatus;
    use App\Support\JalaliDate;
@endphp

<x-layouts.workspace art="resume-bank" title="حضور در بانک رزومه" heading="حضور در بانک رزومه"
                     lede="با پیوستن به بانک، کارفرماهای تأییدشده کارت ناشناس شما را می‌بینند و می‌توانند درخواست تماس بفرستند. تا شما نپذیرید، نام و شماره‌تان را نمی‌بینند."
                     nav="resume-bank" help="resume-bank">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif
    @error('bank')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    <div class="grid items-start gap-8 xl:grid-cols-[minmax(0,1fr)_26rem]">
        <section aria-labelledby="requests-heading" class="min-w-0">
            <h2 id="requests-heading" class="text-h3 text-ink">درخواست‌های تماس</h2>
            <p class="mt-1 text-copy text-muted">هر درخواست تا @fa($replyDays) روز منتظر پاسخ شما می‌ماند. پذیرفتن فقط به همان شرکت نام، موبایل و ایمیل شما را نشان می‌دهد.</p>

            @if ($requests->isEmpty())
                <x-empty-state art="empty-bank-requests" icon="bell" class="mt-4" title="هنوز درخواستی نرسیده"
                               :description="$passport->in_bank ? 'وقتی کارفرمایی کارت شما را بپسندد، درخواستش این‌جا می‌آید و اعلان می‌گیرید.' : 'اول به بانک رزومه بپیوندید؛ تا عضو نباشید، هیچ کارفرمایی کارت شما را نمی‌بیند.'" />
            @else
                <ul class="mt-4 flex list-none flex-col gap-3 ps-0">
                    @foreach ($requests as $bankRequest)
                        <li class="flex flex-col gap-3 rounded-xl border border-line bg-surface px-5 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-h4 text-ink">
                                    @if (Route::has('jobs.companies.show') && $bankRequest->company->isListed())
                                        <a href="{{ route('jobs.companies.show', $bankRequest->company->slug) }}">{{ $bankRequest->company->name }}</a>
                                    @else
                                        {{ $bankRequest->company->name }}
                                    @endif
                                </span>
                                <x-badge :tone="$bankRequest->status->tone()">{{ $bankRequest->status->label() }}</x-badge>
                            </div>
                            @if ($bankRequest->note)
                                <p class="text-copy text-body">{{ $bankRequest->note }}</p>
                            @endif
                            <p class="text-note text-muted">
                                رسیده {{ JalaliDate::long($bankRequest->created_at) }}
                                @if ($bankRequest->status === BankRequestStatus::Pending)
                                    · مهلت پاسخ تا {{ JalaliDate::long($bankRequest->expires_at) }}
                                @endif
                            </p>
                            @if ($bankRequest->isAnswerable())
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('jobs.bank.accept', $bankRequest->uuid) }}">
                                        @csrf
                                        <x-button type="submit" variant="primary" size="sm">پذیرفتن و نمایش تماس</x-button>
                                    </form>
                                    <form method="POST" action="{{ route('jobs.bank.decline', $bankRequest->uuid) }}">
                                        @csrf
                                        <x-button type="submit" variant="secondary" size="sm">رد</x-button>
                                    </form>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="flex flex-col gap-6">
            <x-card title="عضویت" heading="text-h4">
                @if ($passport->in_bank)
                    <p class="text-copy text-body">عضو بانک رزومه هستید، از {{ JalaliDate::long($passport->bank_joined_at) }}.</p>
                    <form method="POST" action="{{ route('jobs.bank.leave') }}" class="mt-4">
                        @csrf
                        <x-button type="submit" variant="secondary">بیرون آمدن از بانک</x-button>
                    </form>
                    <p class="mt-2 text-note text-muted">درخواست‌های بی‌پاسخ رد می‌شوند. شرکتی که پیش‌تر پذیرفته‌اید، راه تماس را دارد.</p>
                @else
                    <p class="text-copy text-body">عضو نیستید و هیچ کارفرمایی شما را در بانک نمی‌بیند.</p>
                    <form method="POST" action="{{ route('jobs.bank.join') }}" class="mt-4">
                        @csrf
                        <x-button type="submit" variant="primary">پیوستن به بانک رزومه</x-button>
                    </form>
                    <p class="mt-2 text-note text-muted">حضور همیشه رایگان است و هر وقت بخواهید بیرون می‌آیید.</p>
                @endif
            </x-card>

            <section aria-labelledby="card-heading">
                <h2 id="card-heading" class="text-h4 text-ink">آنچه کارفرما می‌بیند</h2>
                <p class="mb-3 mt-1 text-note text-muted">
                    از گذرنامه مهارتی شما ساخته می‌شود.
                    @if (Route::has('jobs.passport.edit'))
                        <a href="{{ route('jobs.passport.edit') }}">ویرایش گذرنامه</a>
                    @endif
                </p>
                @include('jobs::bank._card', ['card' => $card])
            </section>
        </div>
    </div>

</x-layouts.workspace>
