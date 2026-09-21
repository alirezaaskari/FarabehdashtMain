<x-layouts.public title="محصولات من" description="مدیریت فایل و قالب‌های شما در فروشگاه.">

    <x-page-header title="محصولات من" lede="هر محصول پیش از انتشار باید تأیید مدیر را بگیرد.">
        <x-slot:actions>
            <x-button :href="route('commerce.vendor.sales')" variant="secondary">گزارش فروش</x-button>
            <x-button :href="route('commerce.vendor.settlement')" variant="secondary">تسویه</x-button>
            <x-button :href="route('commerce.vendor.products.create')" variant="primary">محصول تازه</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-8">
        @if ($products->isEmpty())
            <x-empty-state icon="file"
                           title="هنوز محصولی نساخته‌اید"
                           description="اولین فایل یا قالب خود را اضافه کنید.">
                <x-slot:action>
                    <x-button :href="route('commerce.vendor.products.create')" variant="primary" size="sm">
                        محصول تازه
                    </x-button>
                </x-slot:action>
            </x-empty-state>
        @else
            <x-card size="lg">
                <ul class="divide-y divide-line">
                    @foreach ($products as $product)
                        <li class="flex items-center justify-between gap-4 py-4">
                            <div>
                                <a href="{{ route('commerce.vendor.products.edit', $product) }}"
                                   class="text-sm font-bold text-ink no-underline hover:no-underline">
                                    {{ $product->title }}
                                </a>
                                <p class="mt-1 text-sm text-muted">{{ $product->price()->format() }}</p>
                            </div>

                            <x-badge :tone="$product->status->tone()">{{ $product->status->label() }}</x-badge>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>

</x-layouts.public>
