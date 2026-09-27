<x-layouts.workspace art="jobs-checkout-failed" title="پرداخت انجام نشد" heading="پرداخت انجام نشد"
                     lede="هیچ مبلغی از حساب شما کم نشده و آگهی همان‌طور که بود مانده است." nav="employer-postings">

    <x-card>
        <x-alert tone="error" title="پرداخت ناموفق">{{ $reason ?: 'دلیل مشخصی از درگاه برنگشت.' }}</x-alert>

        <x-button :href="route('jobs.employer.postings.checkout', $posting->uuid)" variant="primary" class="mt-6">تلاش دوباره</x-button>
    </x-card>

</x-layouts.workspace>
