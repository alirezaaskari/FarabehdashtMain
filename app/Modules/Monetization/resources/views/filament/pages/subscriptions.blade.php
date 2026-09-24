@php
    $catalog = app(App\Modules\Monetization\Services\PlanCatalog::class);
    $planRows = $this->planRows($catalog);
    $subscriberRows = $this->subscriberRows();
@endphp

<x-filament-panels::page>

    <x-filament::section heading="قیمت پلن‌ها">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            قیمت هر دوره پرداخت‌شده روی خودش ثبت شده است؛ تغییر این عددها هیچ صورتحساب
            گذشته‌ای را جابه‌جا نمی‌کند.
        </p>

        <div class="mt-4 flex flex-col gap-4">
            @foreach ($planRows as $plan)
                <div class="flex items-end gap-3">
                    <div class="grow">
                        <label for="price-{{ $plan['slug'] }}"
                               class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $plan['title'] }} ({{ $plan['cycle'] }}) — اکنون {{ $plan['price'] }}
                        </label>

                        <x-filament::input.wrapper class="mt-2">
                            <x-filament::input id="price-{{ $plan['slug'] }}" type="text" dir="ltr" data-numeric
                                               wire:model="prices.{{ $plan['slug'] }}" />
                        </x-filament::input.wrapper>
                    </div>
                </div>
            @endforeach

            <div>
                <x-filament::button wire:click="savePrices">ذخیره قیمت‌ها</x-filament::button>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="مشترکان فعال">
        @if ($subscriberRows === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز مشترک فعالی نیست.</p>
        @else
            <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($subscriberRows as $row)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <p class="text-sm text-gray-950 dark:text-white">
                            <span dir="ltr" data-numeric>{{ $row['mobile'] }}</span>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            تا <span dir="ltr" data-numeric>{{ $row['ends'] }}</span> ·
                            {{ $row['status'] }} ·
                            <span dir="ltr" data-numeric>{{ $row['seats'] }}</span> صندلی
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    <x-filament::section heading="صندلی تیمی">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            صندلی فقط دسترسی حرفه‌ای را به حساب مستقل عضو اضافه می‌کند. هیچ دسترسی‌ای به
            داده عضو به صاحب اشتراک داده نمی‌شود.
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <x-filament::input.wrapper>
                <x-filament::input type="text" dir="ltr" data-numeric
                                   placeholder="موبایل صاحب اشتراک" wire:model="ownerMobile" />
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <x-filament::input type="text" dir="ltr" data-numeric
                                   placeholder="موبایل عضو" wire:model="memberMobile" />
            </x-filament::input.wrapper>

            @if ($error)
                <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
            @endif

            <div class="flex gap-3">
                <x-filament::button wire:click="grantSeat">دادن صندلی</x-filament::button>
                <x-filament::button color="danger" wire:click="revokeSeat">پس‌گرفتن صندلی</x-filament::button>
            </div>
        </div>
    </x-filament::section>

</x-filament-panels::page>
