<x-filament-panels::page>

    <x-filament::section heading="بسته‌ها">
        @if ($bundles === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز بسته‌ای نساخته‌اید؛ از فرم پایین شروع کنید.</p>
        @else
            <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                @foreach ($bundles as $bundle)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="bundle-{{ $bundle['id'] }}">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-950 dark:text-white">
                                @if ($bundle['url'])
                                    <a href="{{ $bundle['url'] }}" target="_blank" class="underline">{{ $bundle['title'] }}</a>
                                @else
                                    {{ $bundle['title'] }}
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $bundle['status'] }} · @fa($bundle['items']) جزء · قیمت بسته {{ $bundle['price'] }} · جمع اجزا {{ $bundle['listTotal'] }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-filament::button size="sm" color="gray" wire:click="edit({{ $bundle['id'] }})">ویرایش</x-filament::button>
                            <x-filament::button size="sm" wire:click="publish({{ $bundle['id'] }})">انتشار</x-filament::button>
                            <x-filament::button size="sm" color="danger" wire:click="retire({{ $bundle['id'] }})">برداشتن از فروش</x-filament::button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

    <x-filament::section :heading="$editingId ? 'ویرایش بسته' : 'بسته تازه'">
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            @foreach (['title' => 'عنوان بسته', 'slug' => 'نشانی (لاتین، مثل noise-assessment)', 'price' => 'قیمت بسته (تومان)'] as $field => $label)
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-gray-950 dark:text-white">{{ $label }}</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="form.{{ $field }}" />
                    </x-filament::input.wrapper>
                </label>
            @endforeach

            <label class="flex flex-col gap-1 text-sm md:col-span-2">
                <span class="font-medium text-gray-950 dark:text-white">معرفی بسته</span>
                <textarea wire:model="form.description" rows="4"
                          class="rounded-lg border border-gray-300 bg-white p-3 text-sm dark:border-white/10 dark:bg-white/5"></textarea>
            </label>

            <fieldset class="flex flex-col gap-4 text-sm md:col-span-2">
                <legend class="font-medium text-gray-950 dark:text-white">اجزای بسته — دست‌کم دو مورد</legend>
                @foreach ($groups as $group)
                    <div>
                        <p class="font-medium text-gray-700 dark:text-gray-200">{{ $group['label'] }}</p>
                        @if ($group['options'] === [])
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">موردی برای انتخاب نیست.</p>
                        @else
                            <div class="mt-2 grid gap-2 md:grid-cols-2">
                                @foreach ($group['options'] as $key => $label)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="form.items" value="{{ $key }}" class="rounded border-gray-300 dark:border-white/10">
                                        <span class="text-gray-700 dark:text-gray-200">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </fieldset>

            <p class="text-xs text-gray-500 dark:text-gray-400 md:col-span-2">
                قیمت بسته باید کمتر از جمع قیمت جداگانه اجزا باشد. پس از هر فروش، سهم هر جزء به نسبت قیمتش حساب
                می‌شود و سهم فروشنده یا مدرس آن جزء، پس از کسر کارمزد، به کیف پولش می‌رود. بسته تازه پیش‌نویس است و
                اگر یکی از اجزا از فروش برداشته شود، دیگر منتشر نمی‌شود.
            </p>

            <div class="flex gap-2 md:col-span-2">
                <x-filament::button type="submit">{{ $editingId ? 'ذخیره تغییرات' : 'ساختن بسته' }}</x-filament::button>
                @if ($editingId)
                    <x-filament::button color="gray" wire:click="cancelEdit">انصراف</x-filament::button>
                @endif
            </div>
        </form>
    </x-filament::section>

</x-filament-panels::page>
