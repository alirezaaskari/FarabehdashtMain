<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            هر پرداخت درگاه اول در «حساب واسط درگاه» می‌نشیند: زرین‌پال پرداخت را تأیید کرده ولی
            پول هنوز به حساب بانکی سایت نرسیده. وقتی واریز زرین‌پال را در صورت‌حساب بانک دیدید،
            مبلغ و شماره پیگیری‌اش را این‌جا ثبت کنید تا به خزانه منتقل شود.
        </p>

        <p class="mt-4 text-sm font-semibold text-gray-950 dark:text-white">
            پول واریزنشده درگاه:
            <span dir="ltr" data-numeric>{{ $outstanding }}</span>
        </p>

        <dl class="mt-2 flex flex-col gap-1 text-sm text-gray-950 dark:text-white">
            @foreach ($escrows as $label => $balance)
                <div class="flex gap-2">
                    <dt>مانده {{ $label }}:</dt>
                    <dd dir="ltr" data-numeric>{{ $balance }}</dd>
                </div>
            @endforeach
        </dl>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            پول امانت هنوز مال سایت نیست: تا پایان کار به مشتری یا مجری بدهکاریم.
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <div>
                <label for="amount" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                    مبلغ واریز (تومان)
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input id="amount" type="text" dir="ltr" data-numeric inputmode="numeric" wire:model="amount" />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label for="reference" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                    شماره پیگیری واریز
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input id="reference" type="text" dir="ltr" data-numeric wire:model="reference" />
                </x-filament::input.wrapper>
            </div>

            @if ($error)
                <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
            @endif

            <div>
                <x-filament::button wire:click="settle" wire:loading.attr="disabled" wire:target="settle">
                    ثبت واریز
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

</x-filament-panels::page>
