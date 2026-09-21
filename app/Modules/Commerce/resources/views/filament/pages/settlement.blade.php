<x-filament-panels::page>

    <x-filament::section>
        <div class="flex items-end gap-3">
            <div class="grow">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" dir="ltr" data-numeric placeholder="09xxxxxxxxx" wire:model="mobile" />
                </x-filament::input.wrapper>
            </div>

            <x-filament::button wire:click="find">جست‌وجو</x-filament::button>
        </div>

        @if ($error)
            <p class="mt-3 text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
        @endif
    </x-filament::section>

    @if ($vendor)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                فروشنده <span dir="ltr" data-numeric>{{ $vendor['mobile'] }}</span> ·
                بدهی فعلی: <strong>{{ $vendor['owed'] }}</strong>
            </p>

            <div class="mt-4 flex flex-col gap-4">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" dir="ltr" data-numeric placeholder="مبلغ تسویه (تومان)" wire:model="amount" />
                </x-filament::input.wrapper>

                <x-filament::input.wrapper>
                    <x-filament::input type="text" placeholder="یادداشت (مثلاً شماره پیگیری واریز)" wire:model="memo" />
                </x-filament::input.wrapper>

                <div>
                    <x-filament::button color="danger" wire:click="settle" wire:loading.attr="disabled" wire:target="settle">
                        ثبت تسویه
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif

</x-filament-panels::page>
