@php
    use App\Support\JalaliDate;

    $groups = $this->groups();
@endphp

<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            بسته راه‌حل، اشتراک حرفه‌ای، اشتراک تیم، بسته آزمون و صدور گزارش. پول همیشه به کیف پول خریدار برمی‌گردد و دسترسی
            همان خرید بسته می‌شود. فایل فروشگاه و دوره صفحه بازگشت وجه خودشان را دارند.
        </p>

        <form wire:submit="find" class="mt-4 flex flex-wrap items-end gap-3">
            <div class="min-w-48 grow">
                <label for="mobile" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">شماره موبایل خریدار</label>
                <x-filament::input.wrapper>
                    <x-filament::input id="mobile" type="text" dir="ltr" data-numeric placeholder="09xxxxxxxxx" wire:model="mobile" />
                </x-filament::input.wrapper>
            </div>
            <x-filament::button type="submit">پیدا کن</x-filament::button>
        </form>

        @if ($error)
            <p class="mt-3 text-sm font-semibold text-danger-600 dark:text-danger-400" role="alert">{{ $error }}</p>
        @endif
    </x-filament::section>

    @if ($buyerId !== null && $groups === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">این کاربر خرید پولی برگشت‌پذیری در این بخش‌ها ندارد.</p>
        </x-filament::section>
    @endif

    @foreach ($groups as $group)
        <x-filament::section :heading="$group['label']">
            <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($group['purchases'] as $purchase)
                    <div class="flex flex-col gap-3 py-4" wire:key="purchase-{{ $purchase->uuid }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $purchase->title }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    پرداخت {{ $purchase->paid->format() }}
                                    @if ($purchase->paidAt)
                                        · {{ JalaliDate::short($purchase->paidAt) }}
                                    @endif
                                </p>
                            </div>
                            @if ($purchase->refunded)
                                <x-filament::badge color="gray">برگشت خورده</x-filament::badge>
                            @elseif (! $purchase->canRefund())
                                <x-filament::badge color="warning">برگشت‌پذیر نیست</x-filament::badge>
                            @endif
                        </div>

                        @if (! $purchase->refunded)
                            @if ($purchase->blocked)
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $purchase->blocked }}</p>
                            @elseif ($purchase->canRefund())
                                <p class="text-sm text-gray-950 dark:text-white">
                                    برمی‌گردد: <span class="font-semibold">{{ $purchase->refundable->format() }}</span>. {{ $purchase->effect }}
                                </p>
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="min-w-48 grow">
                                        <label for="reason-{{ $purchase->uuid }}" class="mb-1 block text-sm text-gray-950 dark:text-white">دلیل بازگشت</label>
                                        <x-filament::input.wrapper>
                                            <x-filament::input id="reason-{{ $purchase->uuid }}" type="text" wire:model="reasons.{{ $purchase->uuid }}" />
                                        </x-filament::input.wrapper>
                                    </div>
                                    @if ($confirming === $purchase->uuid)
                                        <x-filament::button color="danger" wire:click="refund('{{ $group['kind'] }}', '{{ $purchase->uuid }}')">
                                            تأیید برگرداندن {{ $purchase->refundable->format() }}
                                        </x-filament::button>
                                    @else
                                        <x-filament::button color="gray" wire:click="confirm('{{ $purchase->uuid }}')">برگرداندن</x-filament::button>
                                    @endif
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">از این خرید مبلغی نمانده که برگردد.</p>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
