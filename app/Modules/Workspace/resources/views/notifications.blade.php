@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="اعلان‌ها"
                     heading="اعلان‌ها"
                     lede="تأیید محتوا، جابه‌جایی کیف پول و خبر رفع اختلال — همه در همین سایت، بدون پیامک و ایمیل."
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
                            <span class="text-label font-bold text-ink">
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

</x-layouts.workspace>
