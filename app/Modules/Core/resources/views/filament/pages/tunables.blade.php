@php
    use App\Support\PersianNumber;

    $tunables = app(\App\Modules\Core\Services\Tunables::class);
@endphp

<x-filament-panels::page>

    <form wire:submit="save" class="flex flex-col gap-6">
        @foreach ($tunables->sections() as $section => $items)
            <x-filament::section :heading="$section">
                <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($items as $tunable)
                        <div class="flex flex-wrap items-end gap-3 py-4" wire:key="tunable-{{ $tunable->key }}">
                            <div class="min-w-48 grow">
                                <label for="tunable-{{ $tunable->key }}" class="text-sm font-semibold text-gray-950 dark:text-white">{{ $tunable->label }}</label>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    @if ($tunable->hint !== '')
                                        {{ $tunable->hint }} ·
                                    @endif
                                    پیش‌فرض {{ PersianNumber::format($tunables->default($tunable->key)) }} {{ $tunable->unit->label() }}،
                                    مجاز {{ PersianNumber::format($tunable->min) }} تا {{ PersianNumber::format($tunable->max) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input id="tunable-{{ $tunable->key }}" type="text" inputmode="numeric" dir="ltr" data-numeric
                                                       wire:model="values.{{ \App\Modules\Core\Filament\Pages\TunablesPage::field($tunable->key) }}" />
                                </x-filament::input.wrapper>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $tunable->unit->label() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach

        @if ($error)
            <p class="text-sm font-semibold text-danger-600 dark:text-danger-400" role="alert">{{ $error }}</p>
        @endif

        <div>
            <x-filament::button type="submit">ذخیره همه</x-filament::button>
        </div>
    </form>

    @if ($this->elsewhere() !== [])
        <x-filament::section heading="قیمت‌هایی که صفحه خودشان را دارند">
            <ul class="flex flex-col gap-2 text-sm">
                @foreach ($this->elsewhere() as $url => $label)
                    <li><a href="{{ $url }}" class="text-primary-600 underline dark:text-primary-400">{{ $label }}</a></li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif

</x-filament-panels::page>
