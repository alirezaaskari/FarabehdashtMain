@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="character.bell" title="اعلان‌ها"
                     heading="اعلان‌ها"
                     lede="تأیید محتوا، جابه‌جایی کیف پول و خبر رفع اختلال. خبرهای مهم را می‌توانید پیامک هم بگیرید."
                     nav="notifications" help="notifications">

    @if ($unread > 0)
        <x-slot:actions>
            <form method="POST" action="{{ route('workspace.notifications.read-all') }}">
                @csrf
                <x-button type="submit" variant="secondary" icon="check">همه را خواندم</x-button>
            </form>
        </x-slot:actions>
    @endif

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($notifications->isEmpty())
        <x-empty-state icon="bell"
                       title="هنوز اعلانی ندارید"
                       description="وقتی محصول یا دوره‌تان تأیید شود، کیف پولتان شارژ شود یا اختلالی که دنبالش بودید رفع شود، این‌جا خبر می‌دهیم." />
    @else
        <ul class="flex flex-col gap-3">
            @foreach ($notifications as $notification)
                <li>
                    <a href="{{ route('workspace.notifications.open', $notification->uuid) }}"
                       @class([
                           'flex min-h-touch flex-col gap-1 rounded-xl border px-5 py-4 no-underline hover:no-underline',
                           'border-primary-line bg-primary-soft' => ! $notification->isRead(),
                           'border-line bg-surface hover:bg-surface-2' => $notification->isRead(),
                       ])>
                        <span class="flex items-start justify-between gap-3">
                            <span class="text-label font-semibold text-ink">
                                @unless ($notification->isRead())
                                    <span class="sr-only">خوانده‌نشده:</span>
                                @endunless
                                {{ $notification->title }}
                            </span>
                            <span class="shrink-0 text-note text-muted">{{ JalaliDate::short($notification->created_at) }}</span>
                        </span>

                        @if ($notification->body)
                            <span class="text-copy text-muted">{{ $notification->body }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif

    @if ($smsTopics !== null)
        <section aria-labelledby="sms-heading" class="mt-10 rounded-xl border border-line bg-surface px-5 py-5">
            <h2 id="sms-heading" class="text-h4 text-ink">پیامک خبرهای مهم</h2>
            <p class="mt-1 text-copy text-muted">
                روزی حداکثر @fa($smsDailyLimit) پیامک، و هیچ پیامکی از ساعت ۲۲ تا ۸ صبح. پیامک فقط عنوان خبر
                و پیوند آن را دارد؛ خود اعلان همیشه همین‌جا هم می‌ماند.
            </p>

            <form method="POST" action="{{ route('workspace.notifications.sms') }}" class="mt-4 flex flex-col gap-2">
                @csrf
                @foreach ($smsTopics as $row)
                    <x-toggle :name="'topics['.$row['topic']->value.']'"
                              :id="'sms-'.$row['topic']->value"
                              :label="$row['topic']->label()"
                              :description="$row['topic']->description()"
                              :checked="$row['on']" />
                @endforeach

                <div class="mt-2">
                    <x-button type="submit" variant="secondary" icon="check">ذخیره تنظیم پیامک</x-button>
                </div>
            </form>
        </section>
    @endif

</x-layouts.workspace>
