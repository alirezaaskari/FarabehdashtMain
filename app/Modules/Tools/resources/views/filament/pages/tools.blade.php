@php use App\Support\PersianDigits; @endphp

<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            نسخه تازه فرمول از این‌جا ساخته نمی‌شود. نسخه‌گذاری در کد انجام می‌شود تا از
            مسیر بازبینی کد و تاریخچه Git بیرون نیفتد؛ این صفحه فقط بین نسخه‌های موجود
            انتخاب می‌کند. محاسبه‌های ذخیره‌شده با نسخه خودشان باقی می‌مانند و تغییر
            این‌جا رویشان اثر ندارد.
        </p>
    </x-filament::section>

    @foreach ($rows as $row)
        <x-filament::section>
            <div class="flex flex-col gap-4">

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['title'] }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $row['category'] }} ·
                            <span dir="ltr">{{ $row['formula'].'@'.$row['version'] }}</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        @if ($row['enabled'])
                            <x-filament::badge color="success">{{ $row['availability'] }}</x-filament::badge>
                        @else
                            <x-filament::badge color="danger">{{ $row['availability'] }}</x-filament::badge>
                        @endif

                        @if ($row['pinned'] !== '')
                            <x-filament::badge color="warning">نسخه سنجاق‌شده</x-filament::badge>
                        @endif
                    </div>
                </div>

                <dl class="grid gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">آخرین بازبینی علمی</dt>
                        <dd class="font-semibold text-gray-950 dark:text-white">
                            {{ $row['reviewed'] ?? 'ثبت نشده' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">محاسبه‌های ثبت‌شده با نسخه فعلی</dt>
                        <dd class="font-semibold text-gray-950 dark:text-white">
                            {{ PersianDigits::from($row['affected']) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">نسخه‌های موجود در موتور</dt>
                        <dd class="font-semibold text-gray-950 dark:text-white" dir="ltr">
                            {{ implode(' · ', $row['versions']) }}
                        </dd>
                    </div>
                </dl>

                <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <label class="text-xs text-gray-500 dark:text-gray-400" for="pin-{{ $row['slug'] }}">
                        نسخه اجرا:
                    </label>

                    <select id="pin-{{ $row['slug'] }}"
                            wire:change="pin('{{ $row['slug'] }}', $event.target.value)"
                            class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5">
                        <option value="" @selected($row['pinned'] === '')>همیشه آخرین نسخه</option>
                        @foreach ($row['versions'] as $version)
                            <option value="{{ $version }}" @selected($row['pinned'] === $version)>{{ $version }}</option>
                        @endforeach
                    </select>

                    <x-filament::button size="sm" color="gray"
                                        wire:click="markReviewed('{{ $row['slug'] }}')">
                        ثبت بازبینی امروز
                    </x-filament::button>

                    @if ($row['enabled'])
                        <x-filament::button size="sm" color="danger"
                                            wire:click="setEnabled('{{ $row['slug'] }}', false)">
                            غیرفعال کن
                        </x-filament::button>
                    @else
                        <x-filament::button size="sm" color="success"
                                            wire:click="setEnabled('{{ $row['slug'] }}', true)">
                            فعال کن
                        </x-filament::button>
                    @endif
                </div>

            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
