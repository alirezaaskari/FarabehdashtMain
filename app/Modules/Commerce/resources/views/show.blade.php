<x-layouts.public :seo="$seo" active="market">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['فروشگاه', route('commerce.index')], [$product->title, null]]" />
    </x-slot:breadcrumb>

    <x-page-header :title="$product->title" :lede="$product->description">
        <x-slot:actions>
            <form method="POST" action="{{ route('commerce.cart.add', $product) }}">
                @csrf
                <x-button type="submit" variant="primary">افزودن به سبد — {{ $product->price()->format() }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if ($product->versions->isNotEmpty())
        <x-card size="lg" class="mt-8">
            <h2 class="text-h4 text-ink">تاریخچه نسخه‌ها</h2>

            <ul class="mt-4 divide-y divide-line">
                @foreach ($product->versions as $version)
                    <li class="py-3">
                        <p class="text-label font-bold text-ink">
                            نسخه <span dir="ltr" data-numeric>{{ $version->version }}</span>
                        </p>
                        @if ($version->changelog)
                            <p class="mt-1 text-label text-muted">{{ $version->changelog }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

</x-layouts.public>
