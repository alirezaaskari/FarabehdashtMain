<x-filament-panels::page>

    @if ($rows === [] && $changes === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">هیچ دوره‌ای در انتظار بررسی نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($rows as $row)
        <x-filament::section>
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['title'] }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            مدرس: {{ $row['instructor'] }} · قیمت: {{ $row['price'] }} · {{ $row['sessions'] }} جلسه
                        </p>
                    </div>

                    <x-filament::button size="sm" wire:click="publish({{ $row['id'] }})">
                        انتشار
                    </x-filament::button>
                </div>

                <div class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="text"
                                placeholder="دلیل رد — برای مدرس فرستاده می‌شود"
                                wire:model="rejectNotes.{{ $row['id'] }}"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <x-filament::button size="sm" color="danger" wire:click="reject({{ $row['id'] }})">
                        رد
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

    @if ($changes !== [])
        <h2 class="mt-4 text-lg font-bold text-gray-950 dark:text-white">افزوده‌های تازه به دوره‌های منتشرشده</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            دوره منتشر می‌ماند؛ این جلسه‌ها و سؤال‌ها تا تأیید شما به دانشجو نشان داده نمی‌شوند. رد کردن آن‌ها را پاک می‌کند.
        </p>
    @endif

    @foreach ($changes as $row)
        <x-filament::section>
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['title'] }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            مدرس: {{ $row['instructor'] }} · {{ count($row['sessions']) }} جلسه تازه · {{ $row['questions'] }} سؤال تازه
                        </p>
                        @if ($row['sessions'] !== [])
                            <ul class="mt-2 list-inside list-disc text-sm text-gray-700 dark:text-gray-300">
                                @foreach ($row['sessions'] as $title)
                                    <li>{{ $title }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <x-filament::button size="sm" wire:click="approveChanges({{ $row['id'] }})">
                        تأیید افزوده‌ها
                    </x-filament::button>
                </div>

                <div class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="text"
                                placeholder="دلیل رد — برای مدرس فرستاده می‌شود"
                                wire:model="changeNotes.{{ $row['id'] }}"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <x-filament::button size="sm" color="danger" wire:click="rejectChanges({{ $row['id'] }})">
                        رد افزوده‌ها
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
