@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="directory-contacts"
                     title="درخواست‌های تماس من"
                     heading="درخواست‌های تماس من"
                     lede="درخواست‌هایی که از صفحه آزمایشگاه‌ها فرستاده‌اید، با پاسخ هر کدام."
                     nav="directory-contacts"
                     help="directory-contacts">

    @if ($contacts->isEmpty())
        <x-empty-state art="empty-directory-contacts" icon="list"
                       title="هنوز درخواست تماسی نفرستاده‌اید"
                       description="در «خدمات تخصصی» آزمایشگاه مناسب را پیدا کنید و از صفحه‌اش درخواست بفرستید.">
            <x-slot:action>
                <x-button :href="route('consulting.directory.index')" variant="primary">خدمات تخصصی</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <ul class="flex list-none flex-col gap-3 ps-0">
            @foreach ($contacts as $contact)
                <li class="rounded-xl border border-line bg-surface px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <a href="{{ $contact->profile->publicUrl() }}" class="inline-flex min-h-touch items-center text-h4 text-ink">{{ $contact->profile->display_name }}</a>
                        <x-badge :tone="$contact->replied_at ? 'primary' : 'neutral'">{{ $contact->replied_at ? 'پاسخ داده' : 'در انتظار پاسخ' }}</x-badge>
                    </div>
                    <p class="mt-1 text-note text-muted">
                        {{ JalaliDate::short($contact->created_at) }}@if ($contact->service) · {{ $catalog->serviceName($contact->service) }}@endif
                        · {{ $contact->share_mobile ? 'شماره شما نشان داده شده' : 'شماره شما پنهان است' }}
                    </p>
                    <p class="mt-3 whitespace-pre-line text-copy text-body">{{ $contact->message }}</p>
                    @if ($contact->reply)
                        <div class="mt-3 border-s-2 border-primary ps-4">
                            <p class="text-label font-semibold text-ink">پاسخ آزمایشگاه</p>
                            <p class="mt-1 whitespace-pre-line text-copy text-body">{{ $contact->reply }}</p>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $contacts->links() }}</div>
    @endif

</x-layouts.workspace>
