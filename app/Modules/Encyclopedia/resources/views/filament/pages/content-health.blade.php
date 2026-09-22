@php use App\Support\PersianDigits; @endphp

<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            نمره سلامت فقط از چیزهایی ساخته می‌شود که سیستم می‌تواند بسنجد: بازبین و تاریخ
            بازبینی، گذشتن موعد، تعداد منبع نسخه‌دار، تعداد بخش، و داشتن پیوند یا ابزار
            مرتبط. کیفیت نوشتار در این نمره نیست و نباید باشد.
        </p>
    </x-filament::section>

    @if ($rows === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز محتوایی ثبت نشده است.</p>
        </x-filament::section>
    @endif

    @foreach ($rows as $row)
        <x-filament::section>
            <div class="flex flex-col gap-4">

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['title'] }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $row['type'] }} · {{ $row['status'] }}
                            @if ($row['reviewer'])
                                · بازبین: {{ $row['reviewer'] }}
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-filament::badge :color="match ($row['tone']) {
                            'primary' => 'success',
                            'caution' => 'warning',
                            default => 'danger',
                        }">
                            نمره {{ PersianDigits::from($row['score']) }}
                        </x-filament::badge>

                        <x-filament::badge color="gray">{{ $row['freshness'] }}</x-filament::badge>
                    </div>
                </div>

                <dl class="grid gap-2 text-xs text-gray-500 dark:text-gray-400 sm:grid-cols-3">
                    <div>
                        <dt class="font-bold">آخرین بازبینی</dt>
                        <dd>{{ $row['reviewed'] ?? 'ثبت نشده' }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold">بازبینی بعدی</dt>
                        <dd>{{ $row['due'] ?? 'ثبت نشده' }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold">فاصله تا موعد</dt>
                        <dd>
                            @if ($row['daysUntilDue'] === null)
                                ثبت نشده
                            @elseif ($row['daysUntilDue'] < 0)
                                {{ PersianDigits::from(abs($row['daysUntilDue'])) }} روز گذشته
                            @else
                                {{ PersianDigits::from($row['daysUntilDue']) }} روز مانده
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($row['gaps'] !== [])
                    <ul class="flex list-disc flex-col gap-1 ps-5 text-xs text-gray-600 dark:text-gray-300">
                        @foreach ($row['gaps'] as $gap)
                            <li>{{ $gap }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($row['publishable'])
                    <div>
                        <x-filament::button size="sm" wire:click="publish({{ $row['id'] }})">
                            انتشار
                        </x-filament::button>
                    </div>
                @endif

            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
