@php
    $unresolved = $this->unresolved();
@endphp

<x-filament-panels::page>

    <x-filament::section heading="ثبت رویداد تازه">
        <div class="flex flex-col gap-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="service" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">سرویس</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="service" wire:model="service">
                            @foreach ($this->serviceOptions() as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label for="state" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">وضعیت</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="state" wire:model="state">
                            @foreach ($this->stateOptions() as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>

            <div>
                <label for="headline" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">عنوان</label>
                <x-filament::input.wrapper>
                    <x-filament::input id="headline" type="text" wire:model="headline" placeholder="مثلاً: تأخیر در ارسال پیامک کد ورود" />
                </x-filament::input.wrapper>
            </div>

            <div>
                <label for="body" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">توضیح برای کاربران (اختیاری)</label>
                <x-filament::input.wrapper>
                    <textarea id="body" rows="3" wire:model="body"
                              class="block w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 focus:ring-0 dark:text-white"></textarea>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label for="startsAt" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">زمان شروع (خالی یعنی همین حالا؛ آینده یعنی نگهداری برنامه‌ریزی‌شده)</label>
                <x-filament::input.wrapper>
                    <x-filament::input id="startsAt" type="datetime-local" dir="ltr" wire:model="startsAt" />
                </x-filament::input.wrapper>
            </div>

            @if ($error)
                <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
            @endif

            <div>
                <x-filament::button wire:click="report" wire:loading.attr="disabled" wire:target="report">
                    ثبت روی صفحه وضعیت
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="رویدادهای باز و پیش‌رو">
        @if ($unresolved->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">همه سرویس‌ها برقرارند و نگهداری پیش‌رویی ثبت نشده است.</p>
        @else
            <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($unresolved as $incident)
                    <div class="flex flex-col gap-3 py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $incident->title }}</p>
                            <x-filament::badge :color="$incident->state->value === 'outage' ? 'danger' : ($incident->state->value === 'degraded' ? 'warning' : 'gray')">
                                {{ $incident->state->label() }}
                            </x-filament::badge>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $incident->service->label() }} · {{ \App\Support\JalaliDate::longWithTime($incident->started_at) }}
                                @if ($incident->isUpcoming()) · پیش‌رو @endif
                            </span>
                        </div>

                        <x-filament::input.wrapper>
                            <x-filament::input type="text" wire:model="resolutions.{{ $incident->id }}"
                                               placeholder="یادداشت رفع برای کاربران (اختیاری)" />
                        </x-filament::input.wrapper>

                        <div>
                            <x-filament::button size="sm" color="success"
                                                wire:click="resolve({{ $incident->id }})"
                                                wire:loading.attr="disabled">
                                رفع شد
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

</x-filament-panels::page>
