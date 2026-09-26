<x-filament-panels::page>

    <x-filament::tabs>
        @foreach ($this->taxonomies() as $key => $label)
            <x-filament::tabs.item :active="$taxonomy === $key" wire:click="choose('{{ $key }}')">
                {{ $label }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    <x-filament::section :heading="$editingId ? 'ویرایش برچسب' : 'برچسب تازه'">
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-semibold">نام</span>
                <x-filament::input.wrapper :valid="! $errors->has('form.name')">
                    <x-filament::input type="text" wire:model="form.name" placeholder="حلال‌های آروماتیک" />
                </x-filament::input.wrapper>
                @error('form.name') <span class="text-danger-600">{{ $message }}</span> @enderror
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-semibold">نشانی (لاتین)</span>
                <x-filament::input.wrapper :valid="! $errors->has('form.slug')">
                    <x-filament::input type="text" dir="ltr" wire:model="form.slug" placeholder="aromatic-solvents" />
                </x-filament::input.wrapper>
                @error('form.slug') <span class="text-danger-600">{{ $message }}</span> @enderror
            </label>

            <label class="flex flex-col gap-1 text-sm md:col-span-2">
                <span class="font-semibold">توضیح (اختیاری)</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model="form.description" />
                </x-filament::input.wrapper>
            </label>

            <div class="flex gap-3 md:col-span-2">
                <x-filament::button type="submit">{{ $editingId ? 'ذخیره' : 'افزودن' }}</x-filament::button>
                @if ($editingId)
                    <x-filament::button color="gray" wire:click="cancel">انصراف</x-filament::button>
                @endif
            </div>
        </form>
    </x-filament::section>

    <x-filament::section heading="برچسب‌ها">
        @php($terms = $this->terms())

        @if ($terms === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز برچسبی در این دسته‌بندی نیست.</p>
        @else
            <ul class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($terms as $term)
                    <li class="flex flex-wrap items-center gap-3 py-3">
                        <div class="min-w-0 grow">
                            <p class="font-semibold text-gray-950 dark:text-white">{{ $term->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <span dir="ltr">{{ $term->slug }}</span> · روی {{ $this->usage($term->id) }} محتوا
                                @if ($term->description) · {{ $term->description }} @endif
                            </p>
                        </div>

                        <x-filament::button size="sm" color="gray" wire:click="move({{ $term->id }}, true)" :disabled="$loop->first">بالا</x-filament::button>
                        <x-filament::button size="sm" color="gray" wire:click="move({{ $term->id }}, false)" :disabled="$loop->last">پایین</x-filament::button>
                        <x-filament::button size="sm" color="gray" wire:click="edit({{ $term->id }})">ویرایش</x-filament::button>
                        <x-filament::button size="sm" color="danger"
                                            wire:click="delete({{ $term->id }})"
                                            wire:confirm="این برچسب از همه محتواها برداشته می‌شود. حذف شود؟">حذف</x-filament::button>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

</x-filament-panels::page>
