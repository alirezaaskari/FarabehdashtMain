<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm leading-7 text-gray-600 dark:text-gray-300">
            کاربران از پایین صفحه هر ماده گزارش می‌دهند که عدد یا جمله‌ای با منبع نمی‌خواند. هر گزارش را با متن اصلی
            مرجع مقایسه کنید؛ اگر درست بود ماده را از ویرایشگر اصلاح کنید (منبع و تاریخ مراجعه را هم به‌روز کنید) و بعد
            «اصلاح شد» را بزنید. بستن گزارش خودش چیزی را در ماده عوض نمی‌کند.
        </p>
    </x-filament::section>

    <h2 class="text-base font-bold text-gray-950 dark:text-white">در انتظار بررسی</h2>

    @if ($open === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">گزارش بازی نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($open as $row)
        <x-filament::section wire:key="open-{{ $row['id'] }}">
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="text-base font-bold text-gray-950 dark:text-white">{{ $row['substance'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['meta'] }}</p>
                </div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $row['topic'] }}</p>
                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $row['message'] }}</p>
                @if ($row['source'])
                    <p class="text-sm">
                        منبع پیشنهادی:
                        <a href="{{ $row['source'] }}" target="_blank" rel="noopener noreferrer external" dir="ltr"
                           class="font-semibold text-primary-600 underline dark:text-primary-400">{{ $row['source'] }}</a>
                    </p>
                @endif
                <div class="flex flex-wrap gap-2">
                    @if ($row['edit'])
                        <x-filament::button tag="a" :href="$row['edit']" color="gray" icon="heroicon-o-pencil-square">ویرایش ماده</x-filament::button>
                    @endif
                    @if ($row['public'])
                        <x-filament::button tag="a" :href="$row['public']" target="_blank" color="gray" icon="heroicon-o-eye">صفحه عمومی</x-filament::button>
                    @endif
                </div>
                <label class="flex flex-col gap-1 text-sm text-gray-700 dark:text-gray-200">
                    یادداشت مدیر (اختیاری، فقط در پنل)
                    <input type="text" maxlength="500" wire:model="notes.{{ $row['id'] }}"
                           class="min-h-11 rounded-lg border border-gray-300 bg-white px-3 text-sm dark:border-white/10 dark:bg-white/5">
                </label>
                <div class="flex flex-wrap gap-2">
                    <x-filament::button color="success" wire:click="markFixed({{ $row['id'] }})">اصلاح شد</x-filament::button>
                    <x-filament::button color="danger" wire:click="dismiss({{ $row['id'] }})">رد گزارش</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

    @if ($closed !== [])
        <h2 class="text-base font-bold text-gray-950 dark:text-white">تازه‌ترین گزارش‌های بسته‌شده</h2>
        @foreach ($closed as $row)
            <x-filament::section wire:key="closed-{{ $row['id'] }}">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $row['substance'] }} · {{ $row['status'] }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $row['topic'] }}: {{ \Illuminate\Support\Str::limit($row['message'], 160) }}</p>
                @if ($row['note'])
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">یادداشت: {{ $row['note'] }}</p>
                @endif
            </x-filament::section>
        @endforeach
    @endif

</x-filament-panels::page>
