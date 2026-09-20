<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            سربرگ ستون‌ها باید دقیقاً همان چیزی باشد که خروجی CSV هم تولید می‌کند:
            <code dir="ltr">cas_number,name_fa,name_en,formula,molar_mass,physical_state</code>.
            تشخیص «تازه» یا «به‌روزرسانی» روی شماره CAS است، نه نام.
        </p>

        <div class="mt-4">
            <x-filament::button color="gray" wire:click="export">خروجی CSV بانک فعلی</x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="flex flex-col gap-4">
            <input type="file" wire:model="file" accept=".csv,text/csv"
                   class="block w-full text-sm text-gray-700 dark:text-gray-300">

            <div wire:loading wire:target="file" class="text-sm text-gray-500">در حال بارگذاری فایل…</div>

            <div class="flex gap-2">
                <x-filament::button wire:click="preview" :disabled="! $file">پیش‌نمایش تغییرات</x-filament::button>

                @if ($summary && $summary['canApply'])
                    <x-filament::button color="success" wire:click="apply">
                        اجرا و نوشتن روی بانک
                    </x-filament::button>
                @endif
            </div>

            @if ($error)
                <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
            @endif
        </div>
    </x-filament::section>

    @if ($summary)
        <x-filament::section>
            <div class="grid grid-cols-4 gap-4 text-center">
                <div>
                    <span class="block text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['counts']['create'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">ماده تازه</span>
                </div>
                <div>
                    <span class="block text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['counts']['update'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">به‌روزرسانی</span>
                </div>
                <div>
                    <span class="block text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['counts']['unchanged'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">بدون تغییر</span>
                </div>
                <div>
                    <span class="block text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $summary['counts']['invalid'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">نامعتبر</span>
                </div>
            </div>

            @if ($summary['invalidRows'] !== [])
                <div class="mt-5">
                    <h3 class="text-sm font-bold text-danger-600 dark:text-danger-400">ردیف‌های نامعتبر</h3>
                    <ul class="mt-2 flex flex-col gap-1 text-xs text-gray-600 dark:text-gray-300">
                        @foreach ($summary['invalidRows'] as $row)
                            <li>خط {{ $row['line'] }}: {{ $row['reason'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($summary['updateRows'] !== [])
                <div class="mt-5">
                    <h3 class="text-sm font-bold text-gray-950 dark:text-white">به‌روزرسانی‌ها</h3>
                    <ul class="mt-2 flex flex-col gap-2 text-xs text-gray-600 dark:text-gray-300">
                        @foreach ($summary['updateRows'] as $row)
                            <li>
                                <span dir="ltr" class="font-semibold">{{ $row['cas'] }}</span>
                                — {{ $row['name'] }}:
                                {{ implode('، ', $row['changes']) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($summary['createRows'] !== [])
                <div class="mt-5">
                    <h3 class="text-sm font-bold text-gray-950 dark:text-white">مواد تازه</h3>
                    <ul class="mt-2 flex flex-col gap-1 text-xs text-gray-600 dark:text-gray-300">
                        @foreach ($summary['createRows'] as $row)
                            <li><span dir="ltr" class="font-semibold">{{ $row['cas'] }}</span> — {{ $row['name'] }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        ماده تازه همیشه پیش‌نویس می‌ماند؛ برای انتشار باید حد مواجهه با منبع نسخه‌دار اضافه شود.
                    </p>
                </div>
            @endif
        </x-filament::section>
    @endif

</x-filament-panels::page>
