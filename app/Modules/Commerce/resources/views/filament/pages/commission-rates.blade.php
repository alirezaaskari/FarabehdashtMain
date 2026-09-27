@php
    use App\Modules\Commerce\Filament\Pages\CommissionRatesPage;
    use App\Support\JalaliDate;
    use App\Support\PersianDigits;

    $current = $this->current();
    $history = $this->history();
@endphp

<x-filament-panels::page>

    <x-filament::section heading="نرخ هر جریان فروش">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            نرخ تازه از تاریخ اثر به بعد روی فروش‌های تازه می‌نشیند. هر فروش نرخ لحظه خودش را نگه می‌دارد، پس تغییر این‌جا
            هیچ فروش، امانت یا تسویه گذشته‌ای را جابه‌جا نمی‌کند. نرخ ثبت‌شده ویرایش یا حذف نمی‌شود؛ برای برگشت، نرخ تازه ثبت کنید.
        </p>

        <div class="mt-4 flex flex-col divide-y divide-gray-200 dark:divide-white/10">
            @foreach (CommissionRatesPage::FLOWS as $flow => $label)
                <div class="flex flex-wrap items-end gap-3 py-4" wire:key="flow-{{ $flow }}">
                    <div class="min-w-48 grow">
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $label }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">امروز {{ PersianDigits::from(rtrim(rtrim(number_format($current[$flow] / 100, 2, '.', ''), '0'), '.')) }}٪</p>
                    </div>
                    <div>
                        <label for="percent-{{ $flow }}" class="text-sm text-gray-950 dark:text-white">درصد تازه</label>
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input id="percent-{{ $flow }}" type="text" dir="ltr" data-numeric wire:model="percents.{{ $flow }}" />
                        </x-filament::input.wrapper>
                    </div>
                    <div>
                        <label for="date-{{ $flow }}" class="text-sm text-gray-950 dark:text-white">از تاریخ (میلادی)</label>
                        <x-filament::input.wrapper class="mt-1">
                            <x-filament::input id="date-{{ $flow }}" type="date" dir="ltr" data-numeric wire:model="dates.{{ $flow }}" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button wire:click="save('{{ $flow }}')">ثبت نرخ</x-filament::button>
                </div>
            @endforeach
        </div>

        @if ($error)
            <p class="mt-3 text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="سابقه نرخ‌ها">
        @if ($history->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز نرخی ثبت نشده و نرخ پیش‌فرض تنظیمات به کار می‌رود.</p>
        @else
            <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($history as $rate)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="rate-{{ $rate->id }}">
                        <p class="text-sm text-gray-950 dark:text-white">{{ CommissionRatesPage::FLOWS[$rate->flow] ?? $rate->flow }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ PersianDigits::from(rtrim(rtrim(number_format($rate->rate_bp / 100, 2, '.', ''), '0'), '.')) }}٪
                            از {{ JalaliDate::short($rate->effective_from) }} · ثبت {{ JalaliDate::short($rate->created_at) }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

</x-filament-panels::page>
