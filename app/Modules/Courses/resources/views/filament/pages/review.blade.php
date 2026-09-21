<x-filament-panels::page>

    @if ($rows === [])
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

</x-filament-panels::page>
