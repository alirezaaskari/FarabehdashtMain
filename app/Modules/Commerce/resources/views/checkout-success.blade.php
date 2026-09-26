@php
    use Illuminate\Support\Facades\URL;
@endphp

<x-layouts.public title="پرداخت موفق" description="سفارش شما با موفقیت پرداخت شد." active="market">

    <x-page-header art="commerce-checkout-success" title="پرداخت موفق" lede="سفارش شما ثبت و پرداخت شد." />

    <x-card size="lg" class="mt-6">
        <x-alert tone="success" title="سفارش شما آماده است">
            کد پیگیری: <span dir="ltr" data-numeric>{{ $order->uuid }}</span>
        </x-alert>

        <ul class="mt-6 divide-y divide-line">
            @foreach ($order->items as $item)
                <li class="flex items-center justify-between gap-4 py-4">
                    <p class="text-label font-semibold text-ink">{{ $item->product->title }}</p>

                    <x-button :href="URL::temporarySignedRoute('commerce.download', now()->addMinutes(15), ['product' => $item->product])"
                              variant="primary" size="sm">
                        دانلود
                    </x-button>
                </li>
            @endforeach
        </ul>
    </x-card>

</x-layouts.public>
