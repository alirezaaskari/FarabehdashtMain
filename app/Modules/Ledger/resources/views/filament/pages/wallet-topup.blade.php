<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            تنها راه ورود پول به سیستم در نسخه فعلی همین شارژ دستی است. کاربر با
            شماره موبایل پیدا می‌شود؛ اگر کیف پولی نداشته باشد، همین‌جا ساخته می‌شود.
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <div>
                <label for="mobile" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                    شماره موبایل کاربر
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        id="mobile"
                        type="text"
                        dir="ltr"
                        data-numeric
                        placeholder="09xxxxxxxxx"
                        wire:model="mobile"
                    />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label for="amount" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                    مبلغ (تومان)
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        id="amount"
                        type="text"
                        dir="ltr"
                        data-numeric
                        inputmode="numeric"
                        placeholder="۵۰۰۰۰۰"
                        wire:model="amount"
                    />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label for="memo" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                    یادداشت (اختیاری)
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input id="memo" type="text" placeholder="مثلاً شماره پیگیری واریز بانکی" wire:model="memo" />
                </x-filament::input.wrapper>
            </div>

            @if ($error)
                <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
            @endif

            @if ($result)
                <p class="text-sm font-semibold text-success-600 dark:text-success-400">
                    کیف پول <span dir="ltr" data-numeric>{{ $result['mobile'] }}</span> شارژ شد؛
                    موجودی تازه: <span dir="ltr" data-numeric>{{ $result['balance'] }}</span>
                </p>
            @endif

            <div>
                <x-filament::button wire:click="credit" wire:loading.attr="disabled" wire:target="credit">
                    شارژ کیف پول
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

</x-filament-panels::page>
