@php
    $report = $this->report();
    $totals = $report->totals();
@endphp

<x-filament-panels::page>

    <x-filament::section heading="وضعیت">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <dl class="grid grid-cols-2 gap-6 md:grid-cols-4">
                @foreach ([
                    ['صفحه مقصد', $totals['targets']],
                    ['پیوند گذاشته‌شده', $totals['links']],
                    ['متن پیوندخورده', $totals['sources']],
                    ['صفحه یتیم', $totals['orphans']],
                ] as [$label, $value])
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">@fa($value)</dd>
                    </div>
                @endforeach
            </dl>

            <x-filament::button wire:click="rebuild" wire:loading.attr="disabled" wire:target="rebuild" color="gray">
                بازسازی همین حالا
            </x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section heading="مسدودکردن پیوند" description="هر شرطی که پر شود باید برقرار باشد. فقط عبارت: آن عبارت هیچ‌جا پیوند نمی‌خورد. فقط مقصد: هیچ متنی به آن صفحه پیوند نمی‌دهد. مبدأ و مقصد: فقط همان جفت.">
        <div class="flex flex-col gap-4">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="phrase" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">عبارت</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="phrase" type="text" wire:model="phrase" placeholder="مثلاً: بنزن" />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label for="sourceKey" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">کلید صفحه مبدأ</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="sourceKey" type="text" dir="ltr" wire:model="sourceKey" placeholder="encyclopedia:noise-basics" />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label for="targetKey" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">کلید صفحه مقصد</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="targetKey" type="text" dir="ltr" wire:model="targetKey" placeholder="chemicals:benzene" />
                    </x-filament::input.wrapper>
                </div>
            </div>

            @if ($error)
                <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
            @endif

            <div>
                <x-filament::button wire:click="block" wire:loading.attr="disabled" wire:target="block">
                    مسدود کن
                </x-filament::button>
            </div>

            @if ($report->blocks()->isNotEmpty())
                <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($report->blocks() as $rule)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <p class="text-sm text-gray-950 dark:text-white">
                                @if ($rule->phrase) عبارت «{{ $rule->phrase }}» @endif
                                @if ($rule->source_key) · از <span dir="ltr" data-numeric>{{ $rule->source_key }}</span> @endif
                                @if ($rule->target_key) · به <span dir="ltr" data-numeric>{{ $rule->target_key }}</span> @endif
                            </p>
                            <x-filament::button size="sm" color="gray" wire:click="unblock({{ $rule->id }})">
                                برداشتن
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>

    <x-filament::section heading="صفحه‌های یتیم" description="صفحه‌هایی که هیچ متنی به آن‌ها پیوند نمی‌دهد.">
        @php $orphans = $report->orphans(); @endphp

        @if ($orphans->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">هیچ صفحه یتیمی نیست.</p>
        @else
            <ul class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($orphans as $orphan)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-2.5">
                        <a href="{{ $orphan->url }}" target="_blank" class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ $orphan->title }}</a>
                        <span class="text-xs text-gray-500 dark:text-gray-400" dir="ltr" data-numeric>{{ $orphan->key }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

    <x-filament::section heading="پرارجاع‌ترین صفحه‌ها">
        @php $top = $report->mostLinked(); @endphp

        @if ($top->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز پیوندی گذاشته نشده. «بازسازی همین حالا» را بزنید.</p>
        @else
            <ul class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($top as $row)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-2.5">
                        <a href="{{ $row->target_url }}" target="_blank" class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ $row->target_title }}</a>
                        <span class="text-sm text-gray-950 dark:text-white">@fa($row->mentions) ارجاع</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

</x-filament-panels::page>
