@php
    $rows = $this->rows(app(App\Modules\Monetization\Services\StreamRegistry::class));
    $preview = $this->preview(app(App\Modules\Monetization\Services\ShutdownPreview::class));
@endphp

<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            خاموش‌کردن یک جریان هیچ داده‌ای را حذف نمی‌کند. سوابق خرید، اشتراک و تراکنش
            دست‌نخورده می‌مانند و صفحه‌های مربوط به‌جای خطا، به جایگزین هدایت می‌شوند.
        </p>
    </x-filament::section>

    <x-filament::section>
        <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($rows as $row)
                <div class="flex items-center justify-between gap-4 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $row['label'] }}</p>

                        @if (! $row['built'])
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ماژولش هنوز ساخته نشده است.</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <x-filament::badge :color="$row['enabled'] ? 'success' : 'gray'">
                            {{ $row['enabled'] ? 'روشن' : 'خاموش' }}
                        </x-filament::badge>

                        @if ($row['built'])
                            @if ($row['enabled'])
                                <x-filament::button size="sm" color="danger"
                                                    wire:click="choose('{{ $row['value'] }}')">
                                    خاموش‌کردن
                                </x-filament::button>
                            @else
                                <x-filament::button size="sm"
                                                    wire:click="enable('{{ $row['value'] }}')">
                                    روشن‌کردن
                                </x-filament::button>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    @if ($preview)
        <x-filament::section :heading="'پیش از خاموش‌کردن «'.$preview['label'].'»'">
            <dl class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">مشترک فعال</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-950 dark:text-white"
                        dir="ltr" data-numeric>{{ $preview['subscribers'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">درآمد ماهانه متأثر</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-950 dark:text-white">{{ $preview['revenue'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">صفحه‌های پنهان‌شونده</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-950 dark:text-white"
                        dir="ltr" data-numeric>{{ $preview['pages'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">بازگشت وجه لازم</dt>
                    <dd class="mt-1 text-sm font-bold text-gray-950 dark:text-white">{{ $preview['refund'] }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex flex-col gap-4">
                <div>
                    <label for="policy" class="text-sm font-semibold text-gray-950 dark:text-white">
                        رفتار با مشترکان فعلی
                    </label>

                    <x-filament::input.wrapper class="mt-2">
                        <x-filament::input.select id="policy" wire:model="policy">
                            @foreach (App\Modules\Monetization\Domain\Enums\ShutdownPolicy::cases() as $policy)
                                <option value="{{ $policy->value }}">{{ $policy->label() }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                @if ($error)
                    <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
                @endif

                <div class="flex gap-3">
                    <x-filament::button color="danger" wire:click="disable">تأیید خاموشی</x-filament::button>
                    <x-filament::button color="gray" wire:click="choose('')">انصراف</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif

</x-filament-panels::page>
