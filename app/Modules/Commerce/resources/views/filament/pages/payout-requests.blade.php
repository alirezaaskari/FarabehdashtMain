<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            هفته‌ای یک بار به شبای هر درخواست واریز کنید، بعد شماره پیگیری بانک را بنویسید و «واریز شد» را بزنید تا
            بدهی به فروشنده در دفتر کل کم شود و فروشنده اعلان بگیرد. اگر نام صاحب حساب با فروشنده نمی‌خواند یا مانده
            کمتر از مبلغ درخواست شده (مثلاً پس از بازگشت وجه)، با دلیل رد کنید.
        </p>
    </x-filament::section>

    <h2 class="text-lg font-bold text-gray-950 dark:text-white">در انتظار واریز (@fa(count($open)))</h2>

    @if ($open === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">درخواستی در انتظار نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($open as $row)
        <x-filament::section wire:key="payout-{{ $row['id'] }}">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['amount'] }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['vendor'] }} · {{ $row['since'] }}</p>
                    </div>
                    @if ($row['short'])
                        <x-filament::badge color="danger">مانده فعلی فقط {{ $row['owed'] }}</x-filament::badge>
                    @else
                        <x-filament::badge color="gray">مانده فعلی {{ $row['owed'] }}</x-filament::badge>
                    @endif
                </div>

                <dl class="grid gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">شبا</dt>
                        <dd class="font-semibold text-gray-950 dark:text-white" dir="ltr" data-numeric>{{ $row['sheba'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">صاحب حساب</dt>
                        <dd class="font-semibold text-gray-950 dark:text-white">{{ $row['holder'] }}</dd>
                    </div>
                </dl>

                <div class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" dir="ltr" data-numeric placeholder="شماره پیگیری واریز"
                                               wire:model="references.{{ $row['id'] }}" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button size="sm" wire:click="markPaid({{ $row['id'] }})"
                                        wire:loading.attr="disabled" wire:target="markPaid">واریز شد</x-filament::button>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" placeholder="دلیل رد، که فروشنده می‌بیند"
                                               wire:model="reasons.{{ $row['id'] }}" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button size="sm" color="danger" wire:click="reject({{ $row['id'] }})">رد</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

    @if ($recent !== [])
        <h2 class="text-lg font-bold text-gray-950 dark:text-white">تصمیم‌های اخیر</h2>

        <x-filament::section>
            <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                @foreach ($recent as $row)
                    <li class="flex flex-wrap justify-between gap-2 py-2">
                        <span class="text-gray-950 dark:text-white">{{ $row['amount'] }} · {{ $row['vendor'] }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $row['status'] }} · {{ $row['decided'] }} · {{ $row['detail'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif

</x-filament-panels::page>
