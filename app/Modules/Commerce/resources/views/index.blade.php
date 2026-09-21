<x-layouts.public title="فروشگاه" description="فایل و قالب تخصصی بهداشت حرفه‌ای، از فروشندگان تأییدشده." active="market">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['فروشگاه', null]]" />
    </x-slot:breadcrumb>

    <x-page-header title="فروشگاه" lede="فایل و قالب تخصصی، بررسی‌شده پیش از انتشار.">
        <x-slot:actions>
            <x-button :href="route('commerce.cart')" variant="secondary" icon="wallet">سبد خرید</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card size="lg" class="mt-8">
        <form method="GET" action="{{ route('commerce.index') }}">
            <label for="q" class="mb-2 block text-sm font-bold text-ink">جست‌وجو</label>
            <div class="flex gap-2.5">
                <input id="q" type="search" name="q" value="{{ $query }}"
                       placeholder="مثلاً: قالب گزارش نمونه‌برداری"
                       class="h-field min-w-0 grow rounded-md border border-line-strong bg-surface px-3.5 text-base text-ink">
                <x-button type="submit" variant="primary" class="shrink-0">جست‌وجو</x-button>
            </div>
        </form>
    </x-card>

    <div class="mt-6">
        @if ($products->isEmpty())
            <x-empty-state icon="empty-box"
                           title="محصولی پیدا نشد"
                           :description="$query !== '' ? 'عبارت دیگری امتحان کنید.' : 'هنوز محصولی در فروشگاه منتشر نشده است.'" />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <x-card>
                        <a href="{{ route('commerce.show', $product->slug) }}" class="no-underline hover:no-underline">
                            <p class="text-copy font-bold text-ink">{{ $product->title }}</p>
                        </a>
                        <p class="mt-2 text-sm text-muted">{{ $product->price()->format() }}</p>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>

</x-layouts.public>
