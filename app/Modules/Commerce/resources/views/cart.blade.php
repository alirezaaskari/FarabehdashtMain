@php
    use App\Support\Money;
@endphp

<x-layouts.public title="سبد خرید" description="محصولاتی که برای خرید انتخاب کرده‌اید." active="market">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['سبد خرید', null]]" />
    </x-slot:breadcrumb>

    <x-page-header title="سبد خرید" lede="پیش از پرداخت، فهرست را بازبینی کنید." />

    @if ($products->isEmpty())
        <x-empty-state icon="empty-box"
                       title="سبد خرید خالی است"
                       description="فایل‌ها و قالب‌های آماده گزارش و ارزیابی را در فروشگاه ببینید و به سبد اضافه کنید.">
            <x-slot:action>
                <x-button :href="route('commerce.index')" size="sm">رفتن به فروشگاه</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <x-card size="lg" class="mt-6">
            <ul class="divide-y divide-line">
                @foreach ($products as $product)
                    <li class="flex items-center justify-between gap-4 py-4">
                        <div>
                            <p class="text-label font-bold text-ink">{{ $product->title }}</p>
                            <p class="mt-1 text-label text-muted">{{ $product->price()->format() }}</p>
                        </div>
                        <form method="POST" action="{{ route('commerce.cart.remove', $product) }}">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="secondary" size="sm">حذف</x-button>
                        </form>
                    </li>
                @endforeach
            </ul>

            <form method="POST" action="{{ route('commerce.checkout') }}"
                  class="mt-6 flex flex-wrap items-end justify-between gap-4 border-t border-line pt-4">
                @csrf

                <div class="flex flex-col gap-3">
                    <p class="text-h4 font-bold text-ink">
                        جمع کل: {{ Money::toman($total)->format() }}
                    </p>

                    <x-payment-method :total="Money::toman($total)" />
                </div>

                <x-button type="submit" variant="primary">پرداخت</x-button>
            </form>
        </x-card>
    @endif

</x-layouts.public>
