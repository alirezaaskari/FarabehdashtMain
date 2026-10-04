@php
    use App\Modules\Marketplace\Domain\Enums\ContractStatus;
    use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
    use App\Support\JalaliDate;
    use App\Support\Money;
    use App\Support\PersianNumber;
    $revisionsMax = (int) ($limits['revisions_max'] ?? 2);
@endphp

<x-layouts.workspace art="market-contract" :title="'قرارداد: '.$project->title" :heading="$project->title"
                     :lede="$isClient
                         ? 'قرارداد شما با مجری. پول هر مرحله پیش از شروعش در امانت فرابهداشت می‌ماند و با تأیید تحویل به مجری می‌رسد.'
                         : 'قرارداد شما با کارفرما. با رسیدن پول هر مرحله به امانت کار را شروع کنید و تحویل را همین‌جا بگذارید.'"
                     :nav="$isClient ? 'market-projects' : 'market-bids'" help="market-contract">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @error('contract')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    @if ($contract->status === ContractStatus::AwaitingPayment)
        <div class="mb-6">
            <x-alert tone="caution" title="در انتظار پرداخت مرحله اول">
                {{ $isClient ? 'تا ' : 'کارفرما تا ' }}{{ JalaliDate::long($contract->pay_by) }}
                {{ $isClient ? 'مرحله اول را بپردازید؛ وگرنه قرارداد بی‌اثر می‌شود و پروژه دوباره برای پیشنهاد باز می‌شود.' : 'مرحله اول را می‌پردازد. پیش از رسیدن پول به امانت کار را شروع نکنید.' }}
            </x-alert>
        </div>
    @elseif ($contract->status === ContractStatus::Lapsed)
        <div class="mb-6"><x-alert tone="caution" title="قرارداد بی‌اثر شد">مرحله اول در مهلت پرداخت نشد و پولی جابه‌جا نشد.</x-alert></div>
    @elseif ($contract->status === ContractStatus::Cancelled)
        <div class="mb-6"><x-alert tone="caution" title="قرارداد لغو شد">مرحله‌های آزادشده سر جایشان می‌مانند و پول برگشتی در کیف پول کارفرماست.</x-alert></div>
    @elseif ($contract->status === ContractStatus::Completed)
        <div class="mb-6"><x-alert tone="success" title="پروژه تمام شد">همه مرحله‌ها تحویل و آزاد شد.</x-alert></div>
    @endif

    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-labelledby="milestones-heading" class="min-w-0">
            <h2 id="milestones-heading" class="text-h3 text-ink">مرحله‌ها</h2>

            <ol class="mt-4 flex list-none flex-col gap-4 ps-0">
                @foreach ($contract->milestones as $milestone)
                    <li class="rounded-xl border border-line bg-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-h4 text-ink">مرحله @fa($milestone->position): {{ $milestone->title }}</h3>
                                <p class="mt-1 text-note text-muted">
                                    {{ $milestone->amount()->format() }} · @fa($milestone->days) روز کار
                                    @if ($milestone->due_at && $milestone->status->isHeld()) · تحویل تا {{ JalaliDate::long($milestone->due_at) }} @endif
                                </p>
                            </div>
                            <x-badge :tone="$milestone->status->tone()">{{ $milestone->status->label() }}</x-badge>
                        </div>

                        @foreach ($milestone->deliveries as $delivery)
                            <div class="mt-4 rounded-lg border border-line bg-surface-2 p-4">
                                <p class="text-note text-muted">تحویل {{ JalaliDate::long($delivery->created_at) }}</p>
                                <p class="mt-2 whitespace-pre-line text-copy text-body">{{ $delivery->note }}</p>
                                @if ($delivery->files->isNotEmpty())
                                    <ul class="mt-2 flex list-none flex-col ps-0">
                                        @foreach ($delivery->files as $file)
                                            <li><a href="{{ route('market.contracts.file', $file->uuid) }}" class="inline-flex min-h-touch items-center text-label">{{ $file->original_name }}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($delivery->revision_note)
                                    <p class="mt-3 text-note font-semibold text-ink">درخواست اصلاح کارفرما</p>
                                    <p class="mt-1 whitespace-pre-line text-copy text-body">{{ $delivery->revision_note }}</p>
                                @endif
                            </div>
                        @endforeach

                        @foreach ($milestone->disputes as $dispute)
                            <div class="mt-4">
                                <x-alert :tone="$dispute->resolved_at ? 'info' : 'caution'" :title="$dispute->resolved_at ? 'رأی مدیر' : 'اعتراض، در بررسی مدیر'">
                                    <span class="block whitespace-pre-line">{{ $dispute->resolved_at ? $dispute->note : $dispute->reason }}</span>
                                    @if ($dispute->resolved_at)
                                        <span class="mt-1 block">بازگشت به کارفرما: {{ $dispute->toClient()?->format() }}</span>
                                    @endif
                                </x-alert>
                            </div>
                        @endforeach

                        @if ($milestone->refunded_toman && ! $milestone->disputes->contains(fn ($dispute) => $dispute->resolved_at !== null))
                            <p class="mt-3 text-note text-muted">{{ Money::toman($milestone->refunded_toman)->format() }} به کیف پول کارفرما برگشت.</p>
                        @endif

                        @if ($milestone->status === MilestoneStatus::Delivered && $milestone->release_at)
                            <p class="mt-3 text-note text-muted">
                                بی‌پاسخ کارفرما، پول این مرحله {{ JalaliDate::long($milestone->release_at) }} خودکار آزاد می‌شود.
                            </p>
                        @endif

                        @if ($isClient && $payable?->id === $milestone->id)
                            <form method="POST" action="{{ route('market.milestones.pay', $milestone->uuid) }}" class="mt-4 flex flex-col gap-4 border-t border-line pt-4">
                                @csrf
                                <x-payment-method :total="$milestone->amount()" />
                                <div><x-button type="submit" variant="primary">پرداخت {{ $milestone->amount()->format() }} به امانت</x-button></div>
                            </form>
                        @endif

                        @if (! $isClient && in_array($milestone->status, [MilestoneStatus::Funded, MilestoneStatus::Revising], true))
                            <form method="POST" action="{{ route('market.milestones.deliver', $milestone->uuid) }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-4 border-t border-line pt-4">
                                @csrf
                                <div>
                                    <label for="note-{{ $milestone->id }}" class="mb-2 block text-label font-semibold text-ink">توضیح تحویل</label>
                                    <textarea id="note-{{ $milestone->id }}" name="note" rows="4" required
                                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('note') }}</textarea>
                                    @error('note')
                                        <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="files-{{ $milestone->id }}" class="mb-2 block text-label font-semibold text-ink">فایل‌های تحویل (اختیاری)</label>
                                    <input id="files-{{ $milestone->id }}" name="files[]" type="file" multiple class="block min-h-touch text-label text-muted">
                                    @error('files.*')
                                        <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div><x-button type="submit" variant="primary">ثبت تحویل</x-button></div>
                            </form>
                        @endif

                        @if ($isClient && $milestone->status === MilestoneStatus::Delivered)
                            <div class="mt-4 flex flex-col gap-4 border-t border-line pt-4">
                                <form method="POST" action="{{ route('market.milestones.approve', $milestone->uuid) }}">
                                    @csrf
                                    <x-button type="submit" variant="primary">تأیید و آزادسازی {{ $milestone->amount()->format() }}</x-button>
                                </form>
                                @if ($milestone->revisions < $revisionsMax)
                                    <form method="POST" action="{{ route('market.milestones.revise', $milestone->uuid) }}" class="flex flex-col gap-3">
                                        @csrf
                                        <label for="revision-{{ $milestone->id }}" class="text-label font-semibold text-ink">
                                            یا اصلاح بخواهید ({{ PersianNumber::format($revisionsMax - $milestone->revisions) }} بار دیگر)
                                        </label>
                                        <textarea id="revision-{{ $milestone->id }}" name="revision_note" rows="3" required
                                                  class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('revision_note') }}</textarea>
                                        @error('revision_note')
                                            <p class="text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                                        @enderror
                                        <div><x-button type="submit" variant="secondary">درخواست اصلاح</x-button></div>
                                    </form>
                                @endif
                            </div>
                        @endif
                        @if ($contract->status->isOpen() && $milestone->status === MilestoneStatus::Funded)
                            @if ($isClient && $resolution->isOverdue($milestone))
                                <form method="POST" action="{{ route('market.milestones.cancel.overdue', $milestone->uuid) }}" class="mt-4 border-t border-line pt-4">
                                    @csrf
                                    <p class="mb-3 text-note text-muted">مهلت تحویل این مرحله گذشته و تحویلی نرسیده؛ می‌توانید بی‌رأی مدیر لغو کنید و پول کامل برمی‌گردد.</p>
                                    <x-button type="submit" variant="secondary">لغو و بازگشت پول مرحله</x-button>
                                </form>
                            @elseif ($isClient && $milestone->cancel_requested_at)
                                <p class="mt-4 border-t border-line pt-4 text-note text-muted">درخواست لغو شما منتظر پاسخ مجری است.</p>
                            @elseif ($isClient && ! $milestone->delivered_at)
                                <form method="POST" action="{{ route('market.milestones.cancel.request', $milestone->uuid) }}" class="mt-4 border-t border-line pt-4">
                                    @csrf
                                    <x-button type="submit" variant="ghost">درخواست لغو و بازگشت پول</x-button>
                                </form>
                            @elseif (! $isClient && $milestone->cancel_requested_at)
                                <div class="mt-4 flex flex-col gap-3 border-t border-line pt-4">
                                    <p class="text-copy text-body">کارفرما لغو این مرحله و بازگشت پول را خواسته. اگر موافق باشید قرارداد بسته می‌شود؛ اگر نه، کارفرما می‌تواند اعتراض ثبت کند.</p>
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('market.milestones.cancel.respond', $milestone->uuid) }}">
                                            @csrf
                                            <input type="hidden" name="agree" value="1">
                                            <x-button type="submit" variant="secondary">موافقم، پول برگردد</x-button>
                                        </form>
                                        <form method="POST" action="{{ route('market.milestones.cancel.respond', $milestone->uuid) }}">
                                            @csrf
                                            <input type="hidden" name="agree" value="0">
                                            <x-button type="submit" variant="ghost">نمی‌پذیرم، کار ادامه دارد</x-button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if ($contract->status->isOpen() && in_array($milestone->status, [MilestoneStatus::Funded, MilestoneStatus::Delivered, MilestoneStatus::Revising], true))
                            <details class="mt-4 border-t border-line pt-2">
                                <summary class="inline-flex min-h-touch cursor-pointer items-center text-label text-muted">اعتراض به این مرحله</summary>
                                <form method="POST" action="{{ route('market.milestones.dispute', $milestone->uuid) }}" class="mt-2 flex flex-col gap-3">
                                    @csrf
                                    <label for="reason-{{ $milestone->id }}" class="text-label font-semibold text-ink">چه چیزی درست انجام نشد؟</label>
                                    <textarea id="reason-{{ $milestone->id }}" name="reason" rows="3" required
                                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('reason') }}</textarea>
                                    <p class="text-note text-muted">پول این مرحله تا رأی مدیر در امانت می‌ماند و آزادسازی خودکار می‌ایستد. مدیر گفت‌وگو و تحویل‌ها را می‌بیند.</p>
                                    <div><x-button type="submit" variant="secondary">ثبت اعتراض</x-button></div>
                                </form>
                            </details>
                        @endif
                    </li>
                @endforeach
            </ol>

            @if ($canRate || $myRating || $theirRating)
                <section aria-labelledby="rating-heading" class="mt-10">
                    <h2 id="rating-heading" class="text-h3 text-ink">امتیاز {{ $isClient ? 'به مجری' : 'به کارفرما' }}</h2>

                    @if ($canRate)
                        <form method="POST" action="{{ route('market.contracts.rate', $contract->uuid) }}" class="mt-4 flex flex-col gap-4 rounded-xl border border-line bg-surface p-5">
                            @csrf
                            @error('rating')
                                <x-alert tone="error">{{ $message }}</x-alert>
                            @enderror
                            <fieldset>
                                <legend class="mb-2 text-label font-semibold text-ink">از ۱ تا ۵ چقدر راضی بودید؟</legend>
                                <div class="flex flex-wrap gap-x-5">
                                    @foreach ([1 => 'ضعیف', 2 => 'متوسط', 3 => 'خوب', 4 => 'خیلی خوب', 5 => 'عالی'] as $value => $word)
                                        <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                            <input type="radio" name="stars" value="{{ $value }}" required @checked((int) old('stars') === $value) class="size-5 shrink-0 accent-primary">
                                            @fa($value) · {{ $word }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <div>
                                <label for="rating-comment" class="text-label font-semibold text-ink">یک جمله کوتاه (اختیاری)</label>
                                <textarea id="rating-comment" name="comment" rows="2" maxlength="{{ $commentMax }}"
                                          class="mt-2 w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('comment') }}</textarea>
                                <p class="mt-1 text-note text-muted">
                                    {{ $isClient ? 'این جمله بی نام شما روی صفحه عمومی مجری می‌آید.' : 'این جمله فقط برای کارفرما و مدیر است.' }}
                                    امتیاز ثبت‌شده ویرایش نمی‌شود@if ($rateDeadline) و تا {{ JalaliDate::long($rateDeadline) }} فرصت دارید@endif.
                                </p>
                            </div>
                            <div><x-button type="submit" variant="primary">ثبت امتیاز</x-button></div>
                        </form>
                    @endif

                    @if ($myRating)
                        <div class="mt-4 rounded-xl border border-line bg-surface p-5">
                            <p class="text-note text-muted">امتیاز شما</p>
                            <p class="mt-1 text-h4 text-ink">@fa($myRating->stars) از ۵</p>
                            @if ($myRating->visibleComment())
                                <p class="mt-2 text-copy text-body">{{ $myRating->visibleComment() }}</p>
                            @elseif ($myRating->hidden_at)
                                <p class="mt-2 text-note text-muted">متن این امتیاز را مدیر پنهان کرد؛ عدد آن سر جایش است.</p>
                            @endif
                        </div>
                    @endif

                    @if ($theirRating)
                        <div class="mt-4 rounded-xl border border-line bg-surface p-5">
                            <p class="text-note text-muted">امتیاز {{ $isClient ? 'مجری' : 'کارفرما' }} به شما</p>
                            <p class="mt-1 text-h4 text-ink">@fa($theirRating->stars) از ۵</p>
                            @if ($theirRating->visibleComment())
                                <p class="mt-2 text-copy text-body">{{ $theirRating->visibleComment() }}</p>
                            @endif
                        </div>
                    @elseif ($theyRated)
                        <p class="mt-4 text-note text-muted">طرف دیگر امتیازش را داده است؛ پس از ثبت امتیاز شما دیده می‌شود.</p>
                    @endif
                </section>
            @endif
        </section>

        <aside class="flex flex-col gap-5">
            <x-card title="قرارداد" heading="text-h4">
                <dl class="flex flex-col gap-3 text-copy">
                    <div><dt class="text-note text-muted">وضعیت</dt><dd><x-badge :tone="$contract->status->tone()">{{ $contract->status->label() }}</x-badge></dd></div>
                    <div><dt class="text-note text-muted">مبلغ کل</dt><dd class="text-ink">{{ $contract->total()->format() }}</dd></div>
                    <div><dt class="text-note text-muted">آزادشده</dt><dd class="text-ink">{{ $contract->releasedTotal()->format() }}</dd></div>
                    @unless ($isClient)
                        <div>
                            <dt class="text-note text-muted">کمیسیون فرابهداشت</dt>
                            <dd class="text-ink">{{ PersianNumber::percent($contract->commission_bp / 100, $contract->commission_bp % 100 === 0 ? 0 : 1) }} از هر مرحله، فقط از سهم شما</dd>
                        </div>
                    @endunless
                    @if ($provider)
                        <div><dt class="text-note text-muted">مجری</dt><dd><a href="{{ $provider['url'] }}" class="inline-flex min-h-touch items-center">{{ $provider['name'] }}</a></dd></div>
                    @endif
                    <div><dt class="text-note text-muted">پذیرش</dt><dd class="text-ink">{{ JalaliDate::long($contract->created_at) }}</dd></div>
                </dl>
                <div class="mt-5 flex flex-col gap-2">
                    <x-button :href="route('market.bids.show', $contract->bid->uuid)" variant="secondary" block>گفت‌وگو با {{ $isClient ? 'مجری' : 'کارفرما' }}</x-button>
                    @if ($isClient && $contract->status->isOpen() && $contract->holdsNothing())
                        <form method="POST" action="{{ route('market.contracts.cancel', $contract->uuid) }}">
                            @csrf
                            <x-button type="submit" variant="ghost" block>لغو قرارداد</x-button>
                        </form>
                        <p class="text-note text-muted">الان پولی در امانت نیست؛ لغو بی‌هزینه است و مرحله‌های آزادشده سر جایشان می‌مانند.</p>
                    @endif
                </div>
            </x-card>

            @if ($project->files->isNotEmpty() && ($isClient || $contract->status !== ContractStatus::AwaitingPayment))
                <x-card title="پیوست‌های پروژه" heading="text-h4">
                    <ul class="flex list-none flex-col ps-0">
                        @foreach ($project->files as $file)
                            <li><a href="{{ route('market.files.download', $file->uuid) }}" class="inline-flex min-h-touch items-center text-label">{{ $file->original_name }}</a></li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        </aside>
    </div>

    <x-disclaimer class="mt-10">
        فرابهداشت پول هر مرحله را تا تأیید تحویل نگه می‌دارد، ولی کیفیت کار یا انطباق قانونی نتیجه را تضمین نمی‌کند.
        هماهنگی و تحویل فقط درون سایت است و راه تماس بیرونی رد و بدل نمی‌شود.
    </x-disclaimer>

</x-layouts.workspace>
