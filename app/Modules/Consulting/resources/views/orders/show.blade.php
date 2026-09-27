@php use App\Modules\Consulting\Domain\Enums\OrderStatus; use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="درخواست خدمت" :heading="$order->service->title"
                     :lede="$order->service->profile->display_name.' · '.$order->service->kind->label().' · '.$order->price()->format()"
                     :nav="$isConsultant ? 'consulting-incoming' : 'consulting-orders'" help="consulting-order-page">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            $isConsultant ? ['درخواست‌های رسیده', route('consulting.orders.incoming')] : ['درخواست‌های مشاوره من', route('consulting.orders.mine')],
            ['درخواست', null],
        ]" />
    </x-slot:breadcrumb>

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif
    @if ($errors->any())
        <div class="mb-6"><x-alert tone="error">{{ $errors->first() }}</x-alert></div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="flex min-w-0 flex-col gap-6">
            <x-card>
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
                    @if ($order->paid_at)
                        <span class="text-note text-muted">پرداخت: {{ JalaliDate::long($order->paid_at) }}</span>
                    @endif
                </div>

                <dl class="mt-5 grid gap-4">
                    <div>
                        <dt class="text-note font-semibold text-muted">نیاز خریدار</dt>
                        <dd class="mt-1 whitespace-pre-line text-copy text-body">{{ $order->need }}</dd>
                    </div>
                    @if ($order->proposed_times !== [])
                        <div>
                            <dt class="text-note font-semibold text-muted">زمان‌های پیشنهادی</dt>
                            <dd class="mt-1">
                                <ul class="flex list-disc flex-col gap-1 ps-5 text-copy text-body">
                                    @foreach ($order->proposed_times as $time)
                                        <li>{{ $time }}</li>
                                    @endforeach
                                </ul>
                            </dd>
                        </div>
                    @endif
                    @if ($order->due_at && in_array($order->status, [OrderStatus::Accepted, OrderStatus::Disputed], true))
                        <div>
                            <dt class="text-note font-semibold text-muted">مهلت تحویل بررسی</dt>
                            <dd class="mt-1 text-copy text-ink">{{ JalaliDate::longWithTime($order->due_at) }}</dd>
                        </div>
                    @endif
                    @if ($city)
                        <div>
                            <dt class="text-note font-semibold text-muted">شهر بازدید</dt>
                            <dd class="mt-1 text-copy text-body">{{ $city }}</dd>
                        </div>
                    @endif
                    @if ($order->scheduled_for)
                        <div>
                            <dt class="text-note font-semibold text-muted">زمان قطعی</dt>
                            <dd class="mt-1 text-copy text-ink">{{ $order->scheduled_for }}</dd>
                        </div>
                    @endif
                    @if ($order->meeting_link && $order->status->isOpen())
                        <div>
                            <dt class="text-note font-semibold text-muted">پیوند جلسه</dt>
                            <dd class="mt-2">
                                <x-button :href="$order->meeting_link" variant="secondary" size="sm" target="_blank" rel="noopener">ورود به جلسه</x-button>
                                <p class="mt-1 text-note text-muted">جلسه روی سرویس بیرونی برگزار می‌شود؛ فرابهداشت فقط پیوند را نگه می‌دارد.</p>
                            </dd>
                        </div>
                    @endif
                    @if ($buyerMobile)
                        <div>
                            <dt class="text-note font-semibold text-muted">موبایل خریدار (با اجازه خودش)</dt>
                            <dd class="mt-1 text-copy text-body"><span data-numeric dir="ltr">{{ $buyerMobile }}</span></dd>
                        </div>
                    @endif
                    @if ($order->dispute_reason)
                        <div>
                            <dt class="text-note font-semibold text-muted">دلیل اعتراض</dt>
                            <dd class="mt-1 whitespace-pre-line text-copy text-body">{{ $order->dispute_reason }}</dd>
                        </div>
                    @endif
                    @if ($order->close_note)
                        <div>
                            <dt class="text-note font-semibold text-muted">{{ $order->status === OrderStatus::Resolved ? 'رأی مدیر' : 'توضیح' }}</dt>
                            <dd class="mt-1 whitespace-pre-line text-copy text-body">{{ $order->close_note }}</dd>
                        </div>
                    @endif
                    @if ($order->refunded_toman > 0)
                        <div>
                            <dt class="text-note font-semibold text-muted">برگشت به کیف پول خریدار</dt>
                            <dd class="mt-1 text-copy text-body">{{ \App\Support\Money::toman($order->refunded_toman)->format() }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            {{-- گام بعدی، فقط برای طرفی که نوبت اوست. --}}
            @if ($isConsultant && $order->status === OrderStatus::AwaitingConsultant)
                <x-card title="پاسخ شما" heading="text-h4">
                    <p class="text-copy text-muted">تا {{ JalaliDate::long($order->paid_at->copy()->addHours($replyHours)) }} فرصت دارید؛ پس از آن درخواست بسته و پول به خریدار برمی‌گردد.</p>
                    <form method="POST" action="{{ route('consulting.orders.accept', $order->uuid) }}" class="mt-4 flex flex-col gap-4">
                        @csrf
                        @if ($order->isReportReview())
                            <p class="text-copy text-body">با پذیرش، @fa($reviewLimits['due_days'] ?? 5) روز برای فرستادن یادداشت‌ها و جمع‌بندی دارید. متن گزارش پایین همین صفحه است.</p>
                        @else
                            <x-field name="scheduled_for" label="زمان قطعی" :value="old('scheduled_for', $order->proposed_times[0] ?? '')" required
                                     hint="یکی از زمان‌های پیشنهادی را بنویسید یا زمانی که در گفت‌وگو توافق کردید." :error="$errors->first('scheduled_for')" />
                        @endif
                        @if ($order->service->kind->value === 'online')
                            <x-field name="meeting_link" type="url" label="پیوند جلسه (اختیاری)" :value="old('meeting_link')" dir="ltr"
                                     hint="نشانی جلسه در سرویس بیرونی؛ بعداً هم می‌توانید در گفت‌وگو بفرستید." :error="$errors->first('meeting_link')" />
                        @endif
                        <div><x-button type="submit" variant="primary" icon="check">پذیرفتن</x-button></div>
                    </form>
                    <form method="POST" action="{{ route('consulting.orders.decline', $order->uuid) }}" class="mt-6 flex flex-col gap-3 border-t border-line pt-5">
                        @csrf
                        <x-field name="reason" label="دلیل رد" :value="old('reason')" required hint="خریدار این را می‌بیند؛ کل مبلغ به کیف پول او برمی‌گردد." />
                        <div><x-button type="submit" variant="danger">رد درخواست</x-button></div>
                    </form>
                </x-card>
            @endif

            @if ($order->isReportReview())
                @include('consulting::orders._review')
            @endif

            @if ($isConsultant && $order->status === OrderStatus::Accepted && ! $order->isReportReview())
                <x-card title="پس از جلسه" heading="text-h4">
                    <p class="text-copy text-muted">وقتی کار انجام شد این را بزنید. خریدار تأیید یا اعتراض می‌کند؛ بی‌پاسخ، @fa($releaseDays) روز بعد سهم شما آزاد می‌شود.</p>
                    <form method="POST" action="{{ route('consulting.orders.deliver', $order->uuid) }}" class="mt-4">
                        @csrf
                        <x-button type="submit" variant="primary" icon="check">انجام شد</x-button>
                    </form>
                </x-card>
            @endif

            @if (! $isConsultant && in_array($order->status, [OrderStatus::Accepted, OrderStatus::Delivered, OrderStatus::FollowUp], true))
                <x-card :title="match (true) {
                    $order->status === OrderStatus::Delivered => 'کار انجام شد؟',
                    $order->isReportReview() => 'بررسی نرسید یا درست نبود؟',
                    default => 'جلسه برگزار نشد؟',
                }" heading="text-h4">
                    @if ($order->isOverdue())
                        <p class="text-copy text-muted">مهلت تحویل گذشته است. می‌توانید درخواست را لغو کنید تا کل مبلغ به کیف پولتان برگردد.</p>
                        <form method="POST" action="{{ route('consulting.orders.cancel', $order->uuid) }}" class="mt-4">
                            @csrf
                            <x-button type="submit" variant="secondary">لغو و بازگشت پول</x-button>
                        </form>
                    @endif
                    @if ($order->status === OrderStatus::Delivered)
                        <p class="text-copy text-muted">مشاور کار را انجام‌شده اعلام کرده. اگر تا @fa($releaseDays) روز پس از آن نه تأیید کنید نه اعتراض، مبلغ خودکار به مشاور آزاد می‌شود.</p>
                        <form method="POST" action="{{ route('consulting.orders.confirm', $order->uuid) }}" class="mt-4">
                            @csrf
                            <x-button type="submit" variant="primary" icon="check">تأیید می‌کنم</x-button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('consulting.orders.dispute', $order->uuid) }}" class="mt-6 flex flex-col gap-3 border-t border-line pt-5">
                        @csrf
                        <x-field name="reason" label="دلیل اعتراض" :value="old('reason')" required hint="چه چیزی درست انجام نشد؛ مدیر گفت‌وگو و همین متن را می‌خواند." />
                        <div><x-button type="submit" variant="danger">ثبت اعتراض</x-button></div>
                    </form>
                </x-card>
            @endif

            <x-card title="گفت‌وگو" heading="text-h4">
                @if ($order->messages->isEmpty())
                    <p class="text-copy text-muted">هنوز پیامی نیست. هماهنگی زمان و جزئیات را همین‌جا انجام دهید.</p>
                @else
                    <ul class="flex list-none flex-col gap-3 ps-0">
                        @foreach ($order->messages as $message)
                            @php $mine = $message->user_id === auth()->id(); @endphp
                            <li @class(['rounded-lg px-4 py-3', 'bg-primary-soft' => $mine, 'bg-surface-2' => ! $mine])>
                                <p class="text-note font-semibold text-muted">{{ $mine ? 'شما' : ($message->user_id === $order->buyer_id ? 'خریدار' : 'مشاور') }} · {{ JalaliDate::long($message->created_at) }}</p>
                                <p class="mt-1 whitespace-pre-line text-copy text-body">{{ $message->body }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($order->status->isOpen())
                    <form method="POST" action="{{ route('consulting.orders.message', $order->uuid) }}" class="mt-4 flex flex-col gap-3">
                        @csrf
                        <label for="body" class="text-label font-semibold text-ink">پیام تازه</label>
                        <textarea id="body" name="body" rows="3" required
                                  class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('body') }}</textarea>
                        <div><x-button type="submit" variant="secondary">فرستادن</x-button></div>
                    </form>
                @endif
            </x-card>
        </div>

        <x-card title="قاعده‌های این درخواست" heading="text-h4">
            <ul class="flex list-disc flex-col gap-2 ps-5 text-copy text-body">
                <li>مبلغ تا پایان کار در امانت فرابهداشت است.</li>
                <li>رد یا بی‌پاسخی @fa($replyHours) ساعته یعنی بازگشت کامل به کیف پول خریدار.</li>
                <li>پس از «انجام شد»، @fa($releaseDays) روز برای تأیید یا اعتراض خریدار.</li>
                <li>اعتراض را مدیر می‌خواند و بازگشت کامل، آزادسازی یا تقسیم را انتخاب می‌کند.</li>
                @if ($order->isReportReview())
                    <li>بررسی تا @fa($reviewLimits['due_days'] ?? 5) روز پس از پذیرش تحویل می‌شود؛ پس از آن خریدار می‌تواند با بازگشت کامل لغو کند.</li>
                    <li>یک بار پرسش تکمیلی پس از تحویل.</li>
                    <li>این نظر کارشناسی است؛ تأیید رسمی گزارش یا انطباق قانونی نیست.</li>
                @else
                    <li>نظر مشاور کارشناسی است؛ تشخیص پزشکی یا تأیید انطباق قانونی نیست.</li>
                @endif
            </ul>
        </x-card>
    </div>

</x-layouts.workspace>
