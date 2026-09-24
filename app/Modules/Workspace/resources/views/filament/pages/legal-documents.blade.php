@php
    $rows = $this->rows(app(App\Modules\Workspace\Services\LegalLibrary::class));
@endphp

<x-filament-panels::page>

    <x-filament::section heading="نسخه‌های جاری">
        <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($rows as $row)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $row['label'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @if ($row['version'])
                            نسخه <span dir="ltr" data-numeric>{{ $row['version'] }}</span> · {{ $row['date'] }} · {{ $row['change'] }}
                        @else
                            هنوز منتشر نشده
                        @endif
                    </p>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section heading="انتشار نسخه تازه">
        <div class="flex flex-col gap-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="document" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">سند</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="document" wire:model.live="document">
                            @foreach ($this->documentOptions() as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label for="change" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">نوع تغییر</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="change" wire:model="change">
                            @foreach ($this->changeOptions() as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                «تغییر اساسی» در قوانین یا حریم خصوصی، همه کاربران واردشده را پیش از صفحه بعدی‌شان به
                صفحه پذیرش می‌برد. غلط‌گیری و اصلاح نشانی را «اصلاح جزئی» منتشر کنید.
            </p>

            <div>
                <label for="summary" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">خلاصه تغییرات (برای تغییر اساسی اجباری است)</label>
                <x-filament::input.wrapper>
                    <x-filament::input id="summary" type="text" wire:model="summary" />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label for="body" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">متن کامل (بندها با یک خط خالی جدا می‌شوند؛ سرتیتر با «## »)</label>
                <x-filament::input.wrapper>
                    <textarea id="body" rows="16" wire:model="body"
                              class="block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 focus:ring-0 dark:text-white"></textarea>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label for="effectiveAt" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">تاریخ اثر (خالی یعنی همین حالا)</label>
                <x-filament::input.wrapper>
                    <x-filament::input id="effectiveAt" type="datetime-local" dir="ltr" wire:model="effectiveAt" />
                </x-filament::input.wrapper>
            </div>

            @if ($error)
                <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
            @endif

            <div>
                <x-filament::button wire:click="publish" wire:loading.attr="disabled" wire:target="publish">
                    انتشار نسخه تازه
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

</x-filament-panels::page>
