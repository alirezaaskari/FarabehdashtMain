<x-layouts.workspace art="posting-published" title="آگهی منتشر شد" heading="آگهی منتشر شد"
                     :lede="'«'.$posting->title.'» در فهرست کاریابی است.'" nav="employer-postings">

    <x-card>
        <x-alert tone="success" :title="$payment->is_free ? 'انتشار رایگان' : 'پرداخت تأیید شد'">
            آگهی تا {{ $posting->expires_at ? \App\Support\JalaliDate::long($posting->expires_at) : '—' }} زنده است.
            @unless ($payment->is_free)
                کد پیگیری: <span dir="ltr" data-numeric>{{ $payment->uuid }}</span>
            @endunless
        </x-alert>

        <div class="mt-6 flex flex-wrap gap-3">
            <x-button :href="route('jobs.show', $posting->id)" variant="primary">دیدن آگهی</x-button>
            <x-button :href="route('jobs.employer.postings.index')" variant="secondary">آگهی‌های من</x-button>
        </div>
    </x-card>

</x-layouts.workspace>
