<x-filament-panels::page>

    <x-filament::section>
        <div class="flex items-end gap-3">
            <div class="grow">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" dir="ltr" placeholder="شناسه ثبت‌نام (UUID) یا موبایل دانشجو" wire:model="query" />
                </x-filament::input.wrapper>
            </div>

            <x-filament::button wire:click="find">جست‌وجو</x-filament::button>
        </div>

        @if ($error)
            <p class="mt-3 text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
        @endif
    </x-filament::section>

    @foreach ($rows ?? [] as $row)
        <x-filament::section>
            <div class="flex flex-col gap-4">
                <div>
                    <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['course'] }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span dir="ltr">{{ $row['uuid'] }}</span> · {{ $row['source'] }} · {{ $row['paidAt'] }}
                        · جلسه‌های دیده‌شده: {{ $row['progress'] }} · {{ $row['status'] }}
                    </p>
                </div>

                @if ($row['refundable'])
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        به کیف پول دانشجو: <strong>{{ $row['price'] }}</strong><br>
                        از بدهی به مدرس کم می‌شود: <strong>{{ $row['instructor'] }}</strong><br>
                        از درآمد پلتفرم کم می‌شود: <strong>{{ $row['commission'] }}</strong><br>
                        دسترسی دانشجو به دوره بسته می‌شود؛ پیشرفتش می‌ماند اگر دوباره بخرد.
                    </p>

                    <x-filament::input.wrapper>
                        <x-filament::input type="text" placeholder="دلیل (اختیاری، در دفتر رویداد می‌ماند)" wire:model="reasons.{{ $row['id'] }}" />
                    </x-filament::input.wrapper>

                    <div>
                        @if ($confirming === $row['id'])
                            <x-filament::button color="danger" wire:click="confirm({{ $row['id'] }})">
                                تأیید و اجرای بازگشت وجه
                            </x-filament::button>
                        @else
                            <x-filament::button color="gray" wire:click="ask({{ $row['id'] }})">
                                بازگشت وجه
                            </x-filament::button>
                        @endif
                    </div>
                @else
                    <x-filament::badge color="gray">{{ $row['refunded'] ? 'وجه برگشت داده شد' : 'پولی برای برگشت ندارد' }}</x-filament::badge>
                @endif
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
