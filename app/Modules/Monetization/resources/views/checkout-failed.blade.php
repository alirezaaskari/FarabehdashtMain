<x-layouts.public title="پرداخت ناموفق" description="پرداخت اشتراک انجام نشد." active="pro">

    <x-page-header title="پرداخت انجام نشد" lede="هیچ مبلغی از حساب شما کم نشده است." />

    <x-card size="lg" class="mt-6">
        <x-alert tone="error" title="پرداخت ناموفق">
            {{ $reason ?? 'دلیل مشخصی از درگاه برنگشت.' }}
        </x-alert>

        <x-button :href="route('monetization.plans')" variant="primary" class="mt-6">تلاش دوباره</x-button>
    </x-card>

</x-layouts.public>
