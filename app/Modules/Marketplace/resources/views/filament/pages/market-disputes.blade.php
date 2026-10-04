<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            پول هر مرحله تا رأی شما در «امانت وجه پروژه» است و آزادسازی خودکار ایستاده. دلیل اعتراض، تحویل‌ها و گفت‌وگو را بخوانید و یکی را
            انتخاب کنید: بازگشت کامل به کیف پول کارفرما، آزادسازی کامل برای مجری، یا تقسیم (مبلغی که به کارفرما برمی‌گردد؛ کمیسیون فقط از بخش
            آزادشده کم می‌شود). بازگشت یا تقسیم قرارداد را می‌بندد؛ آزادسازی کامل قرارداد را ادامه می‌دهد. توضیح رأی را هر دو طرف می‌بینند.
        </p>
    </x-filament::section>

    <h2 class="text-lg font-bold text-gray-950 dark:text-white">اختلاف‌های باز (@fa(count($rows)))</h2>

    @if ($rows === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">اختلاف بازی نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($rows as $row)
        <x-filament::section wire:key="m-{{ $row['id'] }}">
            <div class="flex flex-col gap-4">
                <div>
                    <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['title'] }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['meta'] }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">دلیل اعتراض</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $row['reason'] }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">تحویل‌ها (@fa(count($row['deliveries'])))</p>
                    @foreach ($row['deliveries'] as $delivery)
                        <div class="mt-2 text-sm text-gray-700 dark:text-gray-200">
                            <p class="whitespace-pre-line">{{ $delivery['note'] }}</p>
                            @foreach ($delivery['files'] as $file)
                                <a href="{{ $file['url'] }}" class="underline">{{ $file['name'] }}</a>
                            @endforeach
                            @if ($delivery['revision'])
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">اصلاح خواسته‌شده: {{ $delivery['revision'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($row['messages'] !== [])
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">گفت‌وگو</p>
                        <ul class="mt-1 flex flex-col gap-1 text-sm">
                            @foreach ($row['messages'] as $message)
                                <li><span class="font-semibold">{{ $message['who'] }}:</span> {{ $message['body'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" placeholder="توضیح رأی، که هر دو طرف می‌بینند"
                                           wire:model="notes.m{{ $row['id'] }}" />
                    </x-filament::input.wrapper>
                    <div class="flex flex-wrap items-end gap-3">
                        <x-filament::button size="sm" color="danger" wire:click="refundAll({{ $row['id'] }})">بازگشت کامل به کارفرما</x-filament::button>
                        <x-filament::button size="sm" wire:click="releaseAll({{ $row['id'] }})">آزادسازی کامل برای مجری</x-filament::button>
                        <div class="w-48">
                            <x-filament::input.wrapper suffix="تومان">
                                <x-filament::input type="number" min="1" max="{{ $row['amount'] }}" placeholder="بازگشتی به کارفرما"
                                                   wire:model="amounts.m{{ $row['id'] }}" />
                            </x-filament::input.wrapper>
                        </div>
                        <x-filament::button size="sm" color="gray" wire:click="split({{ $row['id'] }})">تقسیم</x-filament::button>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
