<x-filament-panels::page>

    <x-filament::section>
        <div class="flex items-end gap-3">
            <div class="grow">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" dir="ltr" placeholder="شناسه سفارش (UUID)" wire:model="orderUuid" />
                </x-filament::input.wrapper>
            </div>

            <x-filament::button wire:click="find">جست‌وجو</x-filament::button>
        </div>

        @if ($error)
            <p class="mt-3 text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
        @endif
    </x-filament::section>

    @if ($items !== null)
        @foreach ($items as $row)
            <x-filament::section>
                <div class="flex flex-col gap-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['product'] }}</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                قیمت: {{ $row['unitPrice'] }} · قابل‌برگشت: {{ $row['remaining'] }}
                            </p>
                        </div>
                    </div>

                    @if ($row['remainingToman'] > 0)
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="grow">
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                        type="text" dir="ltr" data-numeric
                                        placeholder="مبلغ بازگشتی (تومان)"
                                        wire:model="amounts.{{ $row['id'] }}"
                                    />
                                </x-filament::input.wrapper>
                            </div>

                            <x-filament::button size="sm" color="gray" wire:click="preview({{ $row['id'] }})">
                                پیش‌نمایش
                            </x-filament::button>
                        </div>

                        @if (isset($previews[$row['id']]))
                            <x-filament::section>
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    به کیف پول خریدار: <strong>{{ $previews[$row['id']]['wallet'] }}</strong><br>
                                    از بدهی فروشنده کم می‌شود: <strong>{{ $previews[$row['id']]['vendor'] }}</strong><br>
                                    از درآمد پلتفرم کم می‌شود: <strong>{{ $previews[$row['id']]['platform'] }}</strong>
                                </p>

                                <div class="mt-4">
                                    <x-filament::button color="danger" wire:click="confirm({{ $row['id'] }})">
                                        تأیید و اجرای بازگشت وجه
                                    </x-filament::button>
                                </div>
                            </x-filament::section>
                        @endif
                    @else
                        <x-filament::badge color="gray">کامل بازگشت داده شده</x-filament::badge>
                    @endif
                </div>
            </x-filament::section>
        @endforeach
    @endif

</x-filament-panels::page>
