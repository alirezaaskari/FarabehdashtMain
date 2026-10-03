<x-filament-panels::page>

    <x-filament::section heading="حالت بررسی پیام">
        <form wire:submit="saveSettings" class="flex flex-col gap-4">
            <div class="flex flex-col gap-3">
                @foreach ($this->modes() as $option)
                    <label class="flex cursor-pointer items-start gap-3 text-sm">
                        <input type="radio" wire:model="mode" value="{{ $option['value'] }}" class="mt-1">
                        <span>
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $option['label'] }}</span>
                            <span class="block text-gray-600 dark:text-gray-300">{{ $option['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div>
                <label for="words" class="text-sm font-semibold text-gray-950 dark:text-white">کلمه‌های مشکوک (هر خط یکی)</label>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    پیامی که یکی از این‌ها، رشته رقم بلند، ایمیل یا لینک داشته باشد در حالت «فقط مشکوک» نگه داشته می‌شود.
                </p>
                <textarea id="words" wire:model="words" rows="8"
                          class="mt-2 block w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white"></textarea>
            </div>

            <div><x-filament::button type="submit">ذخیره</x-filament::button></div>
            <p class="text-xs text-gray-500 dark:text-gray-400">تغییر حالت فقط روی پیام‌های بعدی اثر دارد؛ پیام‌های در صف همین‌جا می‌مانند.</p>
        </form>
    </x-filament::section>

    <h2 class="text-lg font-bold text-gray-950 dark:text-white">پیام‌های نگه‌داشته (@fa(count($held)))</h2>

    @if ($held === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">پیامی در انتظار نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($held as $row)
        <x-filament::section wire:key="message-{{ $row['id'] }}">
            <div class="flex flex-col gap-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['meta'] }}</p>
                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $row['body'] }}</p>
                @if ($row['flags'] !== [])
                    <p class="text-xs text-danger-600 dark:text-danger-400">مشکوک به: {{ implode('، ', $row['flags']) }}</p>
                @endif
                <div class="flex flex-wrap gap-2 border-t border-gray-200 pt-3 dark:border-white/10">
                    <x-filament::button size="sm" wire:click="approve({{ $row['id'] }})">رساندن</x-filament::button>
                    <x-filament::button size="sm" color="danger" wire:click="reject({{ $row['id'] }})">رد و اخطار</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

    <h2 class="text-lg font-bold text-gray-950 dark:text-white">دسترسی‌های بسته (@fa(count($blocked)))</h2>

    <x-filament::section>
        @if ($blocked === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">کاربری به سقف اخطار نرسیده است.</p>
        @else
            <ul class="flex flex-col divide-y divide-gray-200 text-sm dark:divide-white/10">
                @foreach ($blocked as $row)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-2" wire:key="blocked-{{ $row['user'] }}">
                        <span>کاربر #{{ $row['user'] }} · @fa($row['strikes']) اخطار · آخرین {{ $row['since'] }}</span>
                        <x-filament::button size="sm" color="gray" wire:click="reopen({{ $row['user'] }})">باز کردن دوباره</x-filament::button>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

</x-filament-panels::page>
