<x-filament-panels::page>

    @if ($rows === [] && $changes === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">هیچ محصولی در انتظار بررسی نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($rows as $row)
        <x-filament::section>
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['title'] }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            فروشنده: {{ $row['vendor'] }} · قیمت: {{ $row['price'] }} · {{ $row['versions'] }} نسخه
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
                                placeholder="دلیل رد — برای فروشنده فرستاده می‌شود"
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
        <h2 class="mt-4 text-lg font-bold text-gray-950 dark:text-white">نسخه‌های تازه محصولات منتشرشده</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            محصول منتشر می‌ماند و خریدار تا تأیید شما همان نسخه قبلی را دانلود می‌کند.
        </p>
    @endif

    @foreach ($changes as $row)
        <x-filament::section>
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['title'] }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            فروشنده: {{ $row['vendor'] }} · نسخه فعلی:
                            <span dir="ltr">{{ $row['live'] ?? '—' }}</span>
                        </p>
                        <ul class="mt-2 list-inside list-disc text-sm text-gray-700 dark:text-gray-300">
                            @foreach ($row['versions'] as $version)
                                <li>نسخه <span dir="ltr">{{ $version }}</span></li>
                            @endforeach
                        </ul>
                    </div>

                    <x-filament::button size="sm" wire:click="approveVersions({{ $row['id'] }})">
                        تأیید نسخه تازه
                    </x-filament::button>
                </div>

                <div class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="text"
                                placeholder="دلیل رد — برای فروشنده فرستاده می‌شود"
                                wire:model="changeNotes.{{ $row['id'] }}"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <x-filament::button size="sm" color="danger" wire:click="rejectVersions({{ $row['id'] }})">
                        رد نسخه تازه
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
