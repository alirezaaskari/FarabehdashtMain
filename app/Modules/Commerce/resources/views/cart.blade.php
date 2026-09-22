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
                       description="هنوز محصولی به سبد اضافه نکرده‌اید." />
    @else
        <x-card size="lg" class="mt-6">
            <ul class="divide-y divide-line">
                @foreach ($products as $product)
                    <li class="flex items-center justify-between gap-4 py-4">
                        <div>
                            <p class="text-sm font-bold text-ink">{{ $product->title }}</p>
                            <p class="mt-1 text-sm text-muted">{{ $product->price()->format() }}</p>
                        </div>
                        <form method="POST" action="{{ route('commerce.cart.remove', $product) }}">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="secondary" size="sm">حذف</x-button>
                        </form>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6 flex items-center justify-between border-t border-line pt-4">
                <p class="text-base font-bold text-ink">
                    جمع کل: {{ Money::toman($total)->format() }}
                </p>

                <form method="POST" action="{{ route('commerce.checkout') }}">
                    @csrf
                    <x-button type="submit" variant="primary">پرداخت</x-button>
                </form>
            </div>
        </x-card>
    @endif

</x-layouts.public>
