@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="lab-contacts"
                     title="درخواست‌های تماس رسیده"
                     heading="درخواست‌های تماس رسیده"
                     lede="درخواست‌هایی که کاربران از صفحه آزمایشگاه شما فرستاده‌اند. به هر کدام یک بار پاسخ دهید."
                     nav="lab-contacts"
                     help="lab-contacts">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($contacts === null)
        <x-empty-state art="empty-lab-contacts" icon="user"
                       title="هنوز صفحه عمومی ندارید"
                       description="درخواست تماس فقط از صفحه منتشرشده آزمایشگاه می‌رسد. اول صفحه را بسازید و برای بازبینی بفرستید.">
            <x-slot:action>
                <x-button :href="route('consulting.profile.edit')" variant="primary">ساختن صفحه آزمایشگاه</x-button>
            </x-slot:action>
        </x-empty-state>
    @elseif ($contacts->isEmpty())
        <x-empty-state icon="bell"
                       title="هنوز درخواستی نرسیده"
                       description="وقتی کسی از صفحه آزمایشگاه درخواست تماس بفرستد، این‌جا می‌آید و اعلان می‌گیرید." />
    @else
        <ul class="flex list-none flex-col gap-3 ps-0">
            @foreach ($contacts as $contact)
                <li class="rounded-xl border border-line bg-surface px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-h4 text-ink">{{ $contact->service ? $catalog->serviceName($contact->service) : 'درخواست عمومی' }}</p>
                        <x-badge :tone="$contact->replied_at ? 'neutral' : 'caution'">{{ $contact->replied_at ? 'پاسخ داده' : 'منتظر پاسخ شما' }}</x-badge>
                    </div>
                    <p class="mt-1 text-note text-muted">
                        {{ JalaliDate::short($contact->created_at) }} ·
                        @if ($contact->share_mobile)
                            شماره درخواست‌کننده: <span data-numeric>{{ $contact->user->mobile }}</span>
                        @else
                            درخواست‌کننده شماره‌اش را نشان نداده؛ از همین‌جا پاسخ دهید.
                        @endif
                    </p>
                    <p class="mt-3 whitespace-pre-line text-copy text-body">{{ $contact->message }}</p>
                    @if ($contact->reply)
                        <div class="mt-3 border-s-2 border-primary ps-4">
                            <p class="text-label font-semibold text-ink">پاسخ شما</p>
                            <p class="mt-1 whitespace-pre-line text-copy text-body">{{ $contact->reply }}</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('consulting.contacts.reply', $contact->uuid) }}" class="mt-4 flex flex-col gap-3">
                            @csrf
                            <label for="reply-{{ $contact->uuid }}" class="text-label font-semibold text-ink">پاسخ</label>
                            <textarea id="reply-{{ $contact->uuid }}" name="reply" rows="4" required maxlength="{{ $replyMax }}"
                                      class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink"></textarea>
                            <div><x-button type="submit" variant="primary">فرستادن پاسخ</x-button></div>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
        @error('reply')
            <p class="mt-3 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
        @enderror
        <div class="mt-6">{{ $contacts->links() }}</div>
    @endif

</x-layouts.workspace>
