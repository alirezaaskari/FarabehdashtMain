<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            تازه‌ترین امتیازهای متن‌دار دو طرف قراردادهای بازار پروژه. متن توهین‌آمیز، شخصی یا نامربوط را پنهان کنید؛ عدد امتیاز در میانگین
            می‌ماند و تغییر نمی‌کند. جمله‌های کارفرما درباره مجری بی نام روی صفحه عمومی مجری می‌آید.
        </p>
    </x-filament::section>

    @if ($rows === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز امتیاز متن‌داری ثبت نشده است.</p>
        </x-filament::section>
    @endif

    @foreach ($rows as $row)
        <x-filament::section wire:key="r-{{ $row['id'] }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-base font-bold text-gray-950 dark:text-white">@fa($row['stars']) از ۵</p>
                    <p class="mt-1 whitespace-pre-line text-sm {{ $row['hidden'] ? 'text-gray-400 line-through' : 'text-gray-700 dark:text-gray-200' }}">{{ $row['comment'] }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['meta'] }}</p>
                </div>
                @if ($row['hidden'])
                    <x-filament::button color="gray" wire:click="show({{ $row['id'] }})">نمایش دوباره متن</x-filament::button>
                @else
                    <x-filament::button color="danger" wire:click="hide({{ $row['id'] }})">پنهان کردن متن</x-filament::button>
                @endif
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
