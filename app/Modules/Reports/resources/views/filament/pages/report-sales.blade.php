@php
    use App\Support\JalaliDate;
    use App\Support\PersianDigits;

    $sale = app(App\Modules\Reports\Services\ReportSale::class);
    $purchases = $this->purchases();
    $month = $this->lastThirtyDays();
@endphp

<x-filament-panels::page>

    <x-filament::section heading="قیمت صدور یک گزارش">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            کاربری که اشتراک حرفه‌ای ندارد، صدور یک گزارش را با این مبلغ می‌خرد. قیمت هر خرید روی خودش ثبت می‌شود و
            تغییر این عدد خرید گذشته‌ای را جابه‌جا نمی‌کند.
            @if (! $sale->isOpen())
                <strong>فروش اکنون بسته است</strong>؛ برای بازکردن، کلید «تک‌فروشی گزارش» را در «کلیدهای درآمدزایی» روشن کنید.
            @endif
        </p>

        <div class="mt-4 flex items-end gap-3">
            <div class="grow">
                <label for="report-price" class="text-sm font-semibold text-gray-950 dark:text-white">
                    قیمت (تومان) — اکنون {{ $sale->price()->format() }}
                </label>
                <x-filament::input.wrapper class="mt-2">
                    <x-filament::input id="report-price" type="text" dir="ltr" data-numeric wire:model="price" />
                </x-filament::input.wrapper>
            </div>
            <x-filament::button wire:click="savePrice">ذخیره قیمت</x-filament::button>
        </div>

        @if ($error)
            <p class="mt-3 text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="سی روز اخیر">
        <p class="text-sm text-gray-950 dark:text-white">
            {{ PersianDigits::from($month['count']) }} خرید، جمعاً {{ $month['revenue']->format() }}
        </p>
    </x-filament::section>

    <x-filament::section heading="تازه‌ترین خریدها">
        @if ($purchases->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز خریدی ثبت نشده است.</p>
        @else
            <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($purchases as $purchase)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="purchase-{{ $purchase->id }}">
                        <p class="text-sm text-gray-950 dark:text-white">
                            {{ $purchase->buyer?->name ?: 'کاربر بی‌نام' }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $purchase->price()->format() }} · {{ $purchase->payment_source->label() }}
                            · {{ $purchase->paid_at ? JalaliDate::short($purchase->paid_at) : '' }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

</x-filament-panels::page>
