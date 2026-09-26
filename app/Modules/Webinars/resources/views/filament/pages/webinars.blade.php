<x-filament-panels::page>

    <x-filament::section heading="رویدادها">
        @if ($webinars === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز رویدادی نساخته‌اید؛ از فرم پایین شروع کنید.</p>
        @else
            <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                @foreach ($webinars as $webinar)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="webinar-{{ $webinar['id'] }}">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-950 dark:text-white">
                                @if ($webinar['url'])
                                    <a href="{{ $webinar['url'] }}" target="_blank" class="underline">{{ $webinar['title'] }}</a>
                                @else
                                    {{ $webinar['title'] }}
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $webinar['status'] }} · {{ $webinar['when'] }} · {{ $webinar['price'] }} · @fa($webinar['registered']) از @fa($webinar['capacity']) ثبت‌نام
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-filament::button size="sm" color="gray" wire:click="edit({{ $webinar['id'] }})">ویرایش</x-filament::button>
                            <x-filament::button size="sm" wire:click="publish({{ $webinar['id'] }})">انتشار</x-filament::button>
                            <x-filament::button size="sm" color="danger" wire:click="cancel({{ $webinar['id'] }})"
                                                wire:confirm="رویداد لغو شود؟ مبلغ همه ثبت‌نام‌های پولی به کیف پولشان برمی‌گردد و به همه خبر داده می‌شود.">لغو رویداد</x-filament::button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

    <x-filament::section :heading="$editingId ? 'ویرایش رویداد' : 'رویداد تازه'">
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            @foreach ([
                'title' => 'عنوان',
                'instructor_name' => 'مدرس',
                'slug' => 'نشانی (لاتین، مثل noise-webinar)',
                'duration_minutes' => 'مدت (دقیقه)',
                'capacity' => 'ظرفیت (نفر)',
                'price' => 'قیمت (تومان؛ صفر یعنی رایگان)',
            ] as $field => $label)
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-gray-950 dark:text-white">{{ $label }}</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="form.{{ $field }}" />
                    </x-filament::input.wrapper>
                </label>
            @endforeach

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-gray-950 dark:text-white">زمان شروع (به وقت تهران)</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="datetime-local" wire:model="form.starts_at" />
                </x-filament::input.wrapper>
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-gray-950 dark:text-white">پیوند جلسه (اسکای‌روم یا مشابه)</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="url" dir="ltr" wire:model="form.join_url" placeholder="https://www.skyroom.online/ch/…" />
                </x-filament::input.wrapper>
            </label>

            <label class="flex flex-col gap-1 text-sm md:col-span-2">
                <span class="font-medium text-gray-950 dark:text-white">معرفی رویداد</span>
                <textarea wire:model="form.description" rows="4"
                          class="rounded-lg border border-gray-300 bg-white p-3 text-sm dark:border-white/10 dark:bg-white/5"></textarea>
            </label>

            <label class="flex flex-col gap-1 text-sm md:col-span-2">
                <span class="font-medium text-gray-950 dark:text-white">نشانی ضبط (اختیاری، پس از برگزاری)</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" dir="ltr" wire:model="form.recording_url" placeholder="/courses/noise-webinar-recording" />
                </x-filament::input.wrapper>
            </label>

            <p class="text-xs text-gray-500 dark:text-gray-400 md:col-span-2">
                پیوند جلسه رمزنگاری‌شده نگه داشته می‌شود و فقط به ثبت‌نام‌شده‌ها، از یک ساعت پیش از شروع تا پایان جلسه،
                داده می‌شود. هیچ نام یا شماره‌ای به سرویس برگزاری فرستاده نمی‌شود. برای ضبط: ویدئو را در «دوره‌ها» به شکل
                یک دوره بسازید و نشانی آن دوره را این‌جا بگذارید تا در صفحه رویداد برگزارشده نمایش داده شود.
            </p>

            <div class="flex gap-2 md:col-span-2">
                <x-filament::button type="submit">{{ $editingId ? 'ذخیره تغییرات' : 'ساختن رویداد' }}</x-filament::button>
                @if ($editingId)
                    <x-filament::button color="gray" wire:click="cancelEdit">انصراف</x-filament::button>
                @endif
            </div>
        </form>
    </x-filament::section>

</x-filament-panels::page>
