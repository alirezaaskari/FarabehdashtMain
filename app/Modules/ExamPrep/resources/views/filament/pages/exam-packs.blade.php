<x-filament-panels::page>

    <x-filament::section heading="بسته‌ها">
        @if ($packs === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز بسته‌ای نساخته‌اید؛ از فرم پایین شروع کنید.</p>
        @else
            <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                @foreach ($packs as $pack)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="pack-{{ $pack['id'] }}">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-950 dark:text-white">
                                @if ($pack['url'])
                                    <a href="{{ $pack['url'] }}" target="_blank" class="underline">{{ $pack['title'] }}</a>
                                @else
                                    {{ $pack['title'] }}
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $pack['status'] }} · {{ $pack['price'] }} · @fa($pack['published']) سؤال منتشرشده
                                @if ($pack['pending'] > 0) · @fa($pack['pending']) در انتظار تأیید @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-filament::button size="sm" color="gray" wire:click="edit({{ $pack['id'] }})">ویرایش</x-filament::button>
                            <x-filament::button size="sm" wire:click="publish({{ $pack['id'] }})">انتشار</x-filament::button>
                            <x-filament::button size="sm" color="danger" wire:click="retire({{ $pack['id'] }})">برداشتن از فروش</x-filament::button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

    <x-filament::section :heading="$editingId ? 'ویرایش بسته' : 'بسته تازه'">
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            @foreach ([
                'title' => 'عنوان بسته',
                'exam_name' => 'نام آزمون',
                'slug' => 'نشانی (لاتین، مثل moh-employment)',
                'price' => 'قیمت (تومان)',
                'exam_question_count' => 'تعداد سؤال آزمون شبیه‌سازی',
                'exam_minutes' => 'زمان آزمون (دقیقه)',
            ] as $field => $label)
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

            <label class="flex flex-col gap-1 text-sm md:col-span-2">
                <span class="font-medium text-gray-950 dark:text-white">موضوع‌ها — هر خط یک موضوع</span>
                <textarea wire:model="form.topics" rows="5"
                          class="rounded-lg border border-gray-300 bg-white p-3 text-sm dark:border-white/10 dark:bg-white/5"></textarea>
            </label>

            <p class="text-xs text-gray-500 dark:text-gray-400 md:col-span-2">
                متن بسته نباید وعده قبولی بدهد؛ واژه‌هایی مثل «تضمینی» ذخیره نمی‌شوند. بسته تازه پیش‌نویس است و تا
                سؤال منتشرشده نداشته باشد منتشر نمی‌شود.
            </p>

            <div class="flex gap-2 md:col-span-2">
                <x-filament::button type="submit">{{ $editingId ? 'ذخیره تغییرات' : 'ساختن بسته' }}</x-filament::button>
                @if ($editingId)
                    <x-filament::button color="gray" wire:click="cancelEdit">انصراف</x-filament::button>
                @endif
            </div>
        </form>
    </x-filament::section>

    <x-filament::section heading="ورود سؤال از اکسل (CSV)">
        <div class="flex flex-col gap-4 text-sm">
            <p class="text-gray-600 dark:text-gray-300">
                در اکسل قالب را پر کنید و با «Save As → CSV UTF-8» ذخیره کنید. هر ردیف یک سؤال است: موضوع (همان نام
                موضوع بسته)، سختی (آسان، متوسط، دشوار)، متن سؤال، تا پنج گزینه، شماره گزینه درست، توضیح، عنوان و
                نشانی داخلی پیوند مطالعه و ستون sample با ۱ برای سؤال‌های نمونه رایگان (حداکثر ۱۰ در هر بسته).
                ورود همه یا هیچ است: اگر حتی یک ردیف خطا داشته باشد، هیچ سؤالی نوشته نمی‌شود. سؤال واردشده
                مستقیم منتشر می‌شود.
            </p>

            <div>
                <x-filament::button color="gray" wire:click="template" icon="heroicon-o-arrow-down-tray">دریافت قالب</x-filament::button>
            </div>

            <label class="flex flex-col gap-1">
                <span class="font-medium text-gray-950 dark:text-white">بسته مقصد</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model="importPackId">
                        <option value="">انتخاب کنید</option>
                        @foreach ($packs as $pack)
                            <option value="{{ $pack['id'] }}">{{ $pack['title'] }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            <input type="file" wire:model="file" accept=".csv,text/csv" class="block w-full text-gray-700 dark:text-gray-300">
            <div wire:loading wire:target="file" class="text-gray-500">در حال بارگذاری فایل…</div>

            <div class="flex gap-2">
                <x-filament::button wire:click="previewImport">پیش‌نمایش</x-filament::button>
                @if ($preview && $preview['errors'] === [] && $preview['imported'] > 0)
                    <x-filament::button color="success" wire:click="applyImport">وارد کردن @fa($preview['imported']) سؤال</x-filament::button>
                @endif
            </div>

            @if ($preview)
                @if ($preview['errors'] === [])
                    <p class="font-semibold text-gray-950 dark:text-white">@fa($preview['imported']) سؤال آماده ورود است.</p>
                @else
                    <div>
                        <p class="font-semibold text-danger-600 dark:text-danger-400">فایل @fa(count($preview['errors'])) خطا دارد و چیزی وارد نمی‌شود:</p>
                        <ul class="mt-2 list-disc ps-5 text-gray-700 dark:text-gray-200">
                            @foreach ($preview['errors'] as $error)
                                <li>خط @fa($error['line']): {{ $error['error'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif
        </div>
    </x-filament::section>

</x-filament-panels::page>
