<x-layouts.public title="پرداخت ناموفق" description="پرداخت این سفارش انجام نشد." active="market">

    <x-page-header title="پرداخت ناموفق" lede="نگران نباشید؛ مبلغی از حساب شما کسر نشده است." />

    <x-card size="lg" class="mt-6">
        <x-alert tone="error" title="سفارش پرداخت نشد">
            {{ $reason ?? 'درگاه پرداخت را رد کرد یا فرایند ناتمام ماند.' }}
        </x-alert>

        <div class="mt-6">
            <x-button :href="route('commerce.cart')" variant="primary">بازگشت به سبد خرید</x-button>
        </div>
    </x-card>

</x-layouts.public>
